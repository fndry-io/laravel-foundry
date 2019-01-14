<?php
namespace Foundry\Requests\Types\Contracts;


use Illuminate\Database\Eloquent\Model;

interface Inputable {

	public function getName() : string;

	public function setModel(Model &$model);

	public function getModel() : Model;

	public function hasModel(): bool;

}