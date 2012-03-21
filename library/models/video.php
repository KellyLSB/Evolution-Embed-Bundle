<?php

namespace Bundles\Embed\Models;
use Bundles\SQL\Model;
use Exception;
use e;

class Video extends Model {

	public function parser() {
		return e::$embed->parser()->load($this->__toArray());
	}
	
}