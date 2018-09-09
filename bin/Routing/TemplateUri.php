<?php

namespace Routing {

	class TemplateUri extends Uri {

		protected $MatchPattern = '';
		protected $parameters = array();
		protected $keys = array();

		public function __construct(String $uri)
		{
			parent::__construct($uri);
			$this->parseParameterKeys();
		}

		protected function parseParameterKeys()
		{
			$this->MatchPattern = '/^';
			if (count($this->path)) {
				foreach ($this->path as $file) {
					$this->MatchPattern .= '\/';
					if (preg_match('/^#([A-Za-z0-9]+)$/', $file, $match)){
						$this->parameters[$match[1]] = '';
						$this->keys[] = $match[1];
						$this->MatchPattern .= '[A-Za-z0-9-_.()%#]+';
					} else {
						$this->keys[] = FALSE;
						$this->MatchPattern .= $file;
					}
				}
			} else {
				$this->MatchPattern .= '\/';
			}
			$this->MatchPattern .= '$/';
		}

		protected function parseParameters(Uri $uri)
		{
			for ($i=0; $i < count($this->path) ; $i++) { 
				if ($this->keys[$i]) {
					$this->parameters[$this->keys[$i]] = $uri->path[$i];
				}
			}
		}


		public function match(Uri $uri): bool
		{
			if (preg_match($this->MatchPattern, $uri->getUri(FALSE))) {
				$this->parseParameters($uri);
				return TRUE;
			}
			return FALSE;
		}

		public function getParams(): Array
		{
			return $this->parameters;
		}
	}
}