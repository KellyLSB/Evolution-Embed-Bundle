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
		echo e::embed("http://vimeo.com/25193154")->embed(500,300);

		e\Complete();
	}

}