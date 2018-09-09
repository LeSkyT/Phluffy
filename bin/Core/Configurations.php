<?php

namespace Core {
	class Configurations {
		protected $config_dir;

		public function __construct() {
			$this->config_dir =  $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR;
		}

		public function getRoutes()
		{
			$routesfile = $this->config_dir . 'Routes.json';
			if (file_exists($routesfile)) {
				$routes = json_decode(file_get_contents($routesfile), TRUE);
				// TODO: Error Management.
				return $routes;
			}
		}
	}	
}