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

	public function __construct() {
		$this->services = e::$yaml->load(dirname(__DIR__).'/configure/services.yaml', true);
	}

	public function url($url = '', $loaded = false) {
		/**
		 * Save unparsed url to class
		 * @author Kelly Becker
		 */
		$this->url = $url;

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

	public function load($id) {
		$vid = e::$embed->getVideo($id);
		$this->parsed = unserialize($vid->data);
		$this->service = $vid->service;
		$this->url($vid->url);
		return $this;
	}

	public function save() {
		$vid = e::$embed->newVideo();
		$vid->data = serialize($this->parsed);
		$vid->service = $this->service;
		$vid->url = $this->url;
		$vid->save();
		return $vid;
	}

	public function embed($width = null, $height = null, $extra = array()) {
		if(!isset($this->services['services']))
			throw new Exception("Service info array does not exist in `services.yaml`");
		if(!isset($this->services['services'][$this->service]))
			throw new Exception("`$this->service` info array does not exist in `services.yaml`");

		$embed_code = $this->services['services'][$this->service]['embed'];
		$data = array_merge($this->parsed, $extra,
			!is_null($width) ? array('width' => $width) : array(),
			!is_null($height) ? array('height' => $height) : array()
		);
		foreach($data as $key => $val)
			$embed_code = str_replace('{'.$key.'}', $val, $embed_code);

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

}