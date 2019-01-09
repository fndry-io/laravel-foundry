<?php

namespace Foundry\Requests\Types;


/**
 * Class TextType
 *
 * @package Foundry\Requests\Types
 */
class TextType extends Type{

	protected $multiline = false;

	protected $max_length;

	protected $min_length;

	public function setMultiline($state): Type
	{
		$this->multiline = $state;
		return $this;
	}

	public function isMultiline()
	{
		return $this->multiline;
	}

	public function setMaxLength($length): Type
	{
		$this->max_length = $length;
		return $this;
	}

	public function getMaxLength()
	{
		return $this->max_length;
	}

	public function setMinLength($length): Type
	{
		$this->min_length = $length;
		return $this;
	}

	public function getMinLength()
	{
		return $this->min_length;
	}


}
