<?php

namespace Routing {

	class Route {
		protected $uri;
		protected $caller = array();
		protected $variables;

		public function __construct($uri, $caller = array(), $variables = array())
		{
			$this->uri = $uri;
			$this->caller = $caller;
			$this->variables = $variables;

			if(empty($this->caller)){
				$this->default();
			}
		}

		public function getController(): string
		{
			return $this->caller['Controller'];
		}

		public function getMethod(): string
		{
			return $this->caller['Method'];
		}

		public function getVariables(): array
		{
			return $this->variables;
		}

		protected function default() {
			if (preg_match('/^\/(([A-Za-z]+)((\/([A-Za-z]+))((\/.+)*))?)?$/', $this->uri, $matches)) {

				$this->caller['Controller'] = (empty($matches[2]) ? 'Home' : $matches[2]) . 'Controller';
				$this->caller['Method'] = (empty($matches[5]) ? 'index' : $matches[5]);
				if (!empty($matches[6])) {
					$this->variables = explode('/', substr($matches[6], 1));
				}
			} else {
				throw new \Exception('Malformated URI: \'' . $this->uri . '\'.');
			}
		}

		public function __toString(): string
		{
			$JsonObject = array(
				'Controller' => $this->caller['Controller'],
				'Method' => $this->caller['Method'],
				'Variables' => $this->variables
			);

			return json_encode($JsonObject);
		}
	}
}