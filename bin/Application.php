<?php

class Application {
	protected static $_instance = Null;

	private $routeProvider;
	private $configurations;

	private function __construct()
	{
		$this->routeProvider = new Routing\RouteProvider();
		$this->configurations = new Core\Configurations();
		$this->addRoutes();


	}

	private function addRoutes()
	{
		foreach ($this->configurations->getRoutes() as $route_desc) {
			$this->routeProvider->addRoute($route_desc);
		}
	}

	public function route(): Array
	{
		return $this->routeProvider->findRoute(new Routing\HttpRequest());
	}

	public static function start() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}