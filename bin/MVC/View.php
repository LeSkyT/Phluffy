<?php

namespace MVC {
	class View {

		private static $Path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR;
		private static $Cache = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'Twig' . DIRECTORY_SEPARATOR;
		private static $twig = False;

		protected $path;

		public function __construct($path)
		{
			if (!View::$twig) {
				$loader = new Twig_Loader_Filesystem(View::$Path);
				View::$twig = new Twig_Environment($loader, View::$Cache);	
			}

			$this->path = $path;
		}

		public function render(Array $params = array()): String
		{
			return View::$twig->render($path, $params);
		}
	}
}