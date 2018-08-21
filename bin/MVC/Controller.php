<?php

abstract class Controller {
	
	protected $_view;

	public function __construct($view) {

	}
	
	protected function _set_view($view) {
		$this->_view = $view;
	}
}