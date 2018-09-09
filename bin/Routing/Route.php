<?php

namespace Routing {

	class Route {
		protected $templateUri;
		protected $controller;
		protected $method;
		protected $requestMethod;

		public function __construct(String $templateUri, String $controller, String $method, String $requestMethod = 'GET')
		{
			// TemplateUri.
			$this->templateUri = new TemplateUri($templateUri);

			// Controller.
			$this->controller = $controller;

			// Method.
			$this->method = $method;

			// Http Request method.
			$method_list = array('POST', 'GET', 'DELETE');
			if (in_array(strtoupper($requestMethod), $method_list))
				$this->requestMethod = strtoupper($requestMethod);
			else
				throw new \Exception('Unknown HTTP Request method : \'' . $requestMethod . '\'.');
		}

		public function getController(): String
		{
			return $this->controller;
		}

		public function getMethod(): String
		{
			return $this->method;
		}

		public function getRequestMethod(): String
		{
			return $this->requestMethod;
		}

		public function match(HttpRequest $request): bool
		{
			return $this->templateUri->match($request->getUri()) && $this->getRequestMethod() == $request->getRequestMethod();
		}

		public function getParams(): Array 
		{
			return $this->templateUri->getParams();
		}
	}
}