<?php

namespace Bundles\Embed;
use Bundles\SQL\SQLBundle;
use Exception;
use e;

class Bundle extends SQLBundle {

	public $parser;

	public function __initBundle() {
		$this->parser = new Parser;
	}

	public function __callBundle($url) {
		return $this->parser->url($url);
	}

	public function parser() {
		return $this->parser;
	}

	public function load($id) {
		return $this->parser->load($id);
	}

	public function getEmbedCode($url, $width = null, $height = null, $extra = array()) {
		return $this->parser->url($url)->embed($width, $height, $extra);
	}

	public function route() {
		// http://www.youtube.com/watch?v=3hf41GKdL_k&feature=g-all-f&context=G2acf0e9FAAAAAAAAAAA
		// http://vimeo.com/25193154
		//dump(e::embed("http://vimeo.com/25193154")->save());
		dump($this->getVideo(1)->parser()->save('api'));

		e\Complete();
	}

}