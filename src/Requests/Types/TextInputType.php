<?php

namespace Foundry\Core\Requests\Types;

use Foundry\Core\Requests\Types\Traits\HasMinMax;


/**
 * Class TextType
 *
 * @package Foundry\Requests\Types
 */
class TextInputType extends InputType {

	use HasMinMax;

	protected $multiline = null;

	public function setMultiline( int $number = null ) {
		$this->multiline = $number;

		return $this;
	}

	public function getMultiline() {
		return $this->multiline;
	}

}
