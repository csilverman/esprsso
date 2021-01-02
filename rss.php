<?php

namespace Csilverman\Exprsso;

/*	Include this set of functions in any project.

	If you're including an image in your RSS feed, make sure to process it
	first with makeImageElement(). That sets up URL, MIME type, and length.
	
	[rss]
		[item]
			[title]
			[link]
			[desc]
			[image_url]
			[image_mime]
			[image_length]



*/
	
/*	Config
	======
*/


class Feed
{
	//	__construct runs right after a new object is created. In this case, I'm
	//	using it to initialize settings.
	public function __construct($cfg = null) {

		$cfg_defaults = array(
			'feed_title' => 'A Jolly Title',
			'feed_url' => 'http://the_location_of_your_feed/feed',
			'feed_desc' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.',
			'number_of_items' => 15,
			'liveSiteURL' => 'playlists.csilverman.com/',
			'liveImagesURL' => 'playlists.csilverman.com/images/',
		);

		//	If an object isn't instantiated with any settings, use
		//	the following defaults.
		if($cfg == null) {
			$cfg = $cfg_defaults;
		}
		else {
			//	Not all of the config vars might have been specified in the passed array
			//	So we need to determine which ones weren't specified by the user, in
			//	which case we'll use the defaults.
			
			//	We'll do this by running through all the keys in the default configuration,
			//	seeing which of them aren't in the array provided by the user, 

			foreach ($cfg_defaults as $key => $value) {
				if(!array_key_exists($key, $cfg)) {
					$cfg[$key] = $value;
				}
			}

		}
		$this->feed_title = $cfg['feed_title'];
		$this->feed_url = $cfg['feed_url'];
		$this->feed_desc = $cfg['feed_desc'];
		$this->number_of_items = $cfg['number_of_items'];
		$this->liveSiteURL = $cfg['liveSiteURL'];
		$this->liveImagesURL = $cfg['liveImagesURL'];
	}
	
	
	/*	Templates
		=========
		The following templates contain the HTML and XML required for
		generating RSS feeds and the results of parsed feeds.
	*/
	
	
	public $feed_template[];
	public $item_template[];

	$feed_template['rss_2_0'] = '<?xml version="1.0" encoding="ISO-8859-1" ?>
		<rss version="2.0">
			<channel>
		        <title>[[feed_title]]</title>
		        <link>[[feed_url]]</link>
		        <description>[[feed_desc]]</description>
		        <language>English</language>
		        <image>
	                <title>website Logo</title>
	                <url></url>
	                <link>Link to image</link>
	                <width>width</width>
	                <height>height</height>
		        </image>
		        [[feed]]
			</channel>
		</rss>';
	
	
	$item_template['rss_2_0'] = '<item>
		<title>[[item_title]]</title>
		<link>[[item_link]]</link>
		<description><![CDATA[ [[item_desc]] ]]></description>
		<enclosure url="[[item_image_url]]" type="[[item_image_mime]]" length="[[item_image_length]]" />
	</item>';
	
	public $item_template = '<li class="item">
		<a href="[[link]]">[[title]]</a>
	</li>';

	
	
	/*	Functions
		=========
	*/
	

	/**
	 * assign_tags function. Accepts an associative array of tag-value pairings, and iterates through it, replacing
	 * the slots in the markup with actual values.
	 * 
	 * @access public
	 * @param array $tags
	 * @param string $template
	 * @return string
	 */
	private function assign_tags($tags, $template)
	{
		$template_markup = $template;
		
		foreach ($tags as $key => $value) {
			$tag_to_find = '[['.$key.']]';
			$template_markup = str_ireplace($tag_to_find, $value, $template_markup);
		}
		return $template_markup;
	}
	
	/**
	 * makeImageElement. Passed an image path - generates an array containing the image's full URL.
	 * You'd use this function when constructing the array that will then be turned into an RSS feed. So while
	 * you're scanning your directory or whatever, process any images with makeImageElement().
	 * 
	 * @access public
	 * @param mixed $image
	 * @return void
	 */
	public function makeImageElement($image)
	{
		$imageElement['url'] = $this->liveSiteURL.$this->liveImagesURL;
		$imageElement['mime'] = mime_content_type($image);
		
		$imageElement['length'] = filesize($image);
		
		return $imageElement;
	}


	/**
	 * css_rss__formatItems function. This accepts an array of items and generates an XML-formatted
	 * string - the feed - that will then be added to the final XML markup of the feed.
	 * 
	 * @access public
	 * @param mixed $array_of_items
	 * @return void
	 */
		
	public function formatItems($array_of_items)
	{
		$count = 1;
		
		$rss_feed_list = '';
		
		foreach ($array_of_items as &$item) {
			if($count <= $this->number_of_items) {
				$rss_feed_list .= $this->assign_tags($item, $this->item_template);
				$count++;
			}
		}
		return $rss_feed_list;
	}
	
	
	/**
	 * makeFeed function. Takes a string - the fully formatted RSS feed - and wraps it
	 * in the XML necessary for a feed. Returns the final XML as a string.
	 * 
	 * @access public
	 * @param mixed $formatted_feed
	 * @return void
	 */
	public function makeFeed($formatted_feed) {

		//	Set up master array to plug into the XML template
		$feed['feed_title'] = $this->feed_title;
		$feed['feed_url'] = $this->feed_url;
		$feed['feed_desc'] = $this->feed_desc;
		$feed['feed'] = $formatted_feed;
		
		return $this->assign_tags($feed, $this->feed_template);
	}

	//	Could also be 'array'
	public $format = 'markup';

	/**
	 * get_feed function.
	 * 
	 * @access public
	 * @param mixed $feed_url
	 * @return void
	 */
	public function get_feed($feed_url) {
		
		//	Config
		
		$number_of_items = 10;
		
		$feeds = new SimpleXMLElement(file_get_contents($feed_url));
	
		//	Check to see what version of RSS it is. In some versions, the items are inside the channel; in some versions, they're outside the channel. https://www.xml.com/pub/a/2002/12/18/dive-into-xml.html
	
		$namespaces = $feeds->getNamespaces(true);
		
		//	Add xmlns 
		$versions_where_items_are_outside_channel = array('http://purl.org/rss/1.0/');
	
		$items_outside_channel = array_intersect($versions_where_items_are_outside_channel, $namespaces);
		
		if($items_outside_channel) {
			$item_path = $feeds->item;
		}
		else $item_path = $feeds->channel->item;
	
		$count = 1;
		
		$final_markup = '';
		
		foreach ($item_path as $item) {
			if($count <= $number_of_items) {
				//	$title = $item->title;
				//	$link = $item->link;
				
				if($this->format == 'markup') {
					$feed_item['title'] = $item->title;
					$feed_item['link'] = $item->link;

					$final_markup .= $this->assign_tags($feed_item, $this->item_template);
				}
				else if($this->format == 'array') {
					$feed_item['title'] = $item->title;
					$feed_item['link'] = $item->link;
					
					$feed_array[] = $feed_item;
				}

				$count++;
			}
			else break;
		}
		if($this->format == 'markup') return $final_markup;
		else if($this->format == 'array') return $final_array;
	}
}

$the_rss = new feedObject();

echo $the_rss->get_feed('https://feeds.pinboard.in/rss/secret:41e45cc451d1c542a665/u:csilverman/');


//	get_feed('https://feeds.pinboard.in/rss/secret:41e45cc451d1c542a665/u:csilverman/');

//	get_feed('https://rss.nytimes.com/services/xml/rss/nyt/World.xml');

// var_dump($namespaces);

	
?>