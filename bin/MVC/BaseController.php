<?php

namespace MVC {
	class BaseController {
		protected $params = array();
		protected $view;
		protected $render = True;

		public function __construct() {
		}
	
		public function setView(String $view)
		{
			$this->view = new View($view);
		}

		public function render()
		{
			$this->view->render($this->params);
		}

		protected function doRender(bool $render)
		{
			$this->render = $render;
		}

		protected function redirect(String $uri, int $seconds = 0)
		{
			$url = $_SERVER['HTTP_HOST'] . $uri;
			$header = ($seconds ? 'refresh:' . $seconds . ';url=' . $url : 'location: ' . $url);
			header($header);
			exit;
		}
	}
}