<?php

namespace Bundles\Embed;
use Bundles\SQL\SQLBundle;
use Exception;
use e;

class Bundle extends SQLBundle {

	public function embedCode($url) {

	}

	//http://vimeo.com/26292088
	//http://www.youtube.com/watch?v=NyRZNNEnkQc
	//http://youtu.be/NyRZNNEnkQc

	public function route() {
		$parser = new Parser;
		//dump($parser->load(3)->embed());
		$get = e::$input->get;

		$parser->url("http://www.youtube.com/watch?v=NyRZNNEnkQc&pizza=something");
		dump($parser);

		e\Complete();
	}

}