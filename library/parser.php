<?php

namespace Bundles\Embed;
use Exception;
use e;

class Parser {

	/**
	 * Array of all the services that we can parse
	 * @author Kelly Becker
	 */
	public $services;

	/**
	 * Domain, and Request of the url provided
	 * @author Kelly Becker
	 */
	public $url;
	public $hash;
	public $domain;
	public $request;

	/**
	 * Identified Service
	 * @author Kelly Becker
	 */
	public $service;

	/**
	 * Variables parsed out of urls
	 * @author Kelly Becker
	 */
	public $parsed;

	/**
	 * Video Object
	 * @author Kelly Becker
	 */

	public function __construct() {
		$this->services = e::$yaml->load(dirname(__DIR__).'/configure/services.yaml', true);
	}

	public function url($url = '', $loaded = false) {
		/**
		 * Save unparsed url to class
		 * @author Kelly Becker
		 */
		$this->url = $url;
		$this->hash = md5($url);

		/**
		 * Remove Protocol
		 * @author Kelly Becker
		 */ 
		if(strpos($url, 'http://') !== false) $url = substr($url, 7);
		elseif(strpos($url, 'https://') !== false) $url = substr($url, 8);

		/**
		 * Separate the domain from the request url
		 * @author Kelly Becker
		 */
		list($domain, $request) = explode('/', $url, 2);
		
		/**
		 * Set to the class
		 * @author Kelly Becker
		 */
		$this->domain = $domain;
		$this->request = $request;

		if($loaded === true) return $this;

		/**
		 * Attempt to determine Service
		 * @author Kelly Becker
		 */
		foreach($this->services['urls'] as $domain => $info) {
			if($domain == $this->domain) break;
			if('www.'.$domain == $this->domain) break;
			unset($domain, $info);
		}

		/**
		 * If we cant find the service throw an exception
		 * @author Kelly Becker
		 */
		if(!isset($info)) throw new Exception("We cannot find the proper service for the provided url `$url`");

		/**
		 * Set the service
		 * @author Kelly Becker
		 */
		$this->service = $info['service'];

		/**
		 * Parse the url into variables
		 * @author Kelly Becker
		 */
		$this->__parseRequest($request, $info['request']);

		/**
		 * Save the parsed data
		 * @author Kelly Becker
		 */
		$this->parsed = $request;

		return $this;
	}

	/**
	 * Load a saved video
	 * @author Kelly Becker
	 */
	public function load($id) {
		$vid = e::$embed->getVideo($id);
		$this->parsed = unserialize($vid->data);
		$this->service = $vid->service;
		$this->url($vid->url);
		$this->video = $vid;
		return $this;
	}

	/**
	 * Save a video to an object
	 * @author Kelly Becker
	 */
	public function save($apiData = false) {

		/**
		 * If we already have a video loaded use its model
		 */
		if(!empty($this->video))
			$vid = $this->video;

		/**
		 * If not then create a new model
		 */
		else $vid = e::$embed->newVideo();

		/**
		 * Set the normal data to the model
		 */
		$vid->data = serialize($this->parsed);
		$vid->service = $this->service;
		$vid->hash = md5($this->url);
		$vid->url = $this->url;

		/**
		 * Set information that can be customized but is based of the API
		 */
		try {
			$vid->name = $this->name($apiData);
			$vid->description = $this->description($apiData);
		}
		catch(Exception $e) {}

		/**
		 * Save and return the model
		 */
		$vid->save();
		return $vid;
	}

	/**
	 * Returns a StdClass of the JSON API for the video information
	 */
	public function info() {

		/**
		 * Check if we have a json api for this service
		 */
		$json_url = $this->checkServices('json');

		/**
		 * Replace stuff in the api url for this video
		 */
		foreach($this->parsed as $key => $val)
			$json_url = str_replace('{'.$key.'}', $val, $json_url);

		/**
		 * Return the api result in object format
		 */
		return json_decode(file_get_contents($json_url));
	}

	/**
	 * Access to predefined variabled within the api
	 * (e.g.) Name, Description, Age, Date uploaded, Etc.
	 * @author Kelly Becker
	 */
	public function __call($func, $args) {

		/**
		 * If we already have the data in a db model and we didnt specify to use the api
		 */
		if(!empty($this->video->$func) || (isset($args[0]) && $args[0] !== 'api'))
			return $this->video->$func;

		/**
		 * Make sure we got a map thorugh the json
		 */
		$jsonLocs = $this->checkServices('jsonLocs');

		/**
		 * If we dont have a map to the location of the requested variable
		 * throw an exception
		 */
		if(!isset($jsonLocs[$func]))
			throw new Exception("`$func` is currently not accessible on via a method. It may be available via `info` (returns the raw API result from $this->service).");

		/**
		 * Create an array representing the progression through the api object
		 */
		$toTitle = empty($jsonLocs[$func]) ? array() : explode('->', $jsonLocs[$func]);

		/**
		 * Get our api
		 */
		$info = $this->info();

		/**
		 * Start using the map
		 */
		$ret = $info;
		foreach($toTitle as $to) {

			/**
			 * Handle functions within the map
			 */
			if(!isset($ret->$to)) {
				switch ($to) {

					/**
					 * Array Pop
					 */
					case '(pop)':
						$ret = array_pop($ret);
					break;

					/**
					 * Array Shift
					 */
					case '(shift)':
						$ret = array_shift($ret);
					break;
					
					/**
					 * If we dont recognize the function or request
					 * Ignore it
					 */
					default:
					break;
				}

				continue;
			}

			/**
			 * Move up the object
			 */
			$ret = $ret->$to;
		}

		/**
		 * Return the final result
		 */
		return $ret;
	}

	/**
	 * Generate the embed code
	 * @author Kelly Becker
	 */
	public function embed($width = null, $height = null, $extra = array()) {

		/**
		 * If the embed code doesnt exists error
		 */
		$embed_code = $this->checkServices('embed');

		/**
		 * Merge data into the array to be rendered
		 */
		$data = array_merge($this->parsed, $extra,
			!is_null($width) ? array('width' => $width) : array(),
			!is_null($height) ? array('height' => $height) : array()
		);

		/**
		 * Start replaceing the strings
		 */
		foreach($data as $key => $val)
			$embed_code = str_replace('{'.$key.'}', $val, $embed_code);

		/**
		 * Return the Embed Code
		 */
		return $embed_code;
	}

	private function __parseRequest(&$request, $format) {
		$format_array = preg_split("/{[a-zA-Z]*}/", $format, -1, PREG_SPLIT_NO_EMPTY);

		/**
		 * If we have an empty format with no url data
		 * @author Kelly Becker
		 */
		if(empty($format_array)) {
			$var = str_replace(array('{','}'), '', $format);
			$request = array($var => $request);
			return;
		}

		$return = array();
		$last_var_pos = 0;
		$last_piece_pos = 0;
		foreach($format_array as $piece) {
			
			/**
			 * Find the end of this variable
			 */
			$piece_end_pos = strpos($format, '&', $last_piece_pos + 1);

			/**
			 * Cut the string
			 */
			if(!$piece_end_pos) $var = substr($format, strpos($format, $piece));
			else $var = substr($format, strpos($format, $piece), $piece_end_pos - $last_piece_pos);

			/**
			 * Set this piece end to the last piece end
			 */
			$last_piece_pos = $piece_end_pos;

			/**
			 * Get the name of the variable
			 */
			$var = str_replace(array($piece, '{', '}'), '', $var);

			///////////////////////////
			///////////Break///////////
			///////////////////////////

			/**
			 * Find the end of this variable
			 */
			$var_end_pos = strpos($request, '&', $last_var_pos + 1);

			/**
			 * Cut the string
			 */
			if(!$var_end_pos) $return[$var] = substr($request, strpos($request, $piece));
			else $return[$var] = substr($request, strpos($request, $piece), $var_end_pos - $last_var_pos);

			/**
			 * Set this var end to the last var end
			 */
			$last_var_pos = $var_end_pos;

			/**
			 * Assign the actual values to their respective variables
			 */
			$return[$var] = str_replace($piece, '', $return[$var]);
		}
		
		$request = $return;

	}

	/**
	 * Make sire the services array exists
	 * If not require it. Then make sure the requested service
	 * exists and enfore that it and its requested key is available
	 * @author Kelly Becker
	 */
	private function checkServices($info = false) {
		if(!isset($this->services['services']))
			throw new Exception("Service info array does not exist in `services.yaml`");
		if(!isset($this->services['services'][$this->service]))
			throw new Exception("`$this->service` info array does not exist in `services.yaml`");
		if($info && !isset($this->services['services'][$this->service][$info]))
			throw new Exception("`$info` does not exist on `$this->service` info array in `services.yaml`");

		return $this->services['services'][$this->service][$info];
	}

}