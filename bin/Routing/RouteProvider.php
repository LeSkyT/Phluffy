<?php 

namespace Routing {
	class RouteProvider {
		protected $routes = array();

		public function __construct()
		{
		}

		public function addRoute(Array $route): bool
		{
			if(!isset($route['Uri']) or !isset($route['Controller']) or !isset($route['Action']) or !isset($route['RequestMethod']))
				return FALSE;

			$this->routes[] = new Route($route['Uri'], $route['Controller'], $route['Action'], $route['RequestMethod']);
			return TRUE;
		}

		public function findRoute(HttpRequest $request): Array
		{
			foreach ($this->routes as $route) {
				if ($route->match($request)) {
					return array(
						"Controller" => $route->getController(),
						"Method" => $route->getMethod(),
						"Params" => $route->getParams(),
						"Variables" => $request->getVariables()
					);
				}
			}
			
			return array();
		}
	}
}