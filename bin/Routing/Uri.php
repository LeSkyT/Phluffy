<?php

namespace Routing {
	
	class Uri {

		protected $uriMask = '/^\/([A-Za-z0-9-_.()%#]+\/)*([A-Za-z0-9-_.()%#]+)?(\?([A-Za-z0-9-_.()%#]+=[A-Za-z0-9-_.()%#]+)?(&[A-Za-z0-9-_.()%#]+=[A-Za-z0-9-_.()%#]+)*)?$/';
		protected $path = array();
		protected $variables = array();

		public function __construct(String $uri)
		{
			if ($this->checkUri($uri))
			 $this->parseUri($uri);
		}

		protected function parseUri(String $uri)
		{
			if (preg_match('/\?/', $uri))
				list($path, $variables) = explode('?', $uri);
			else {
				$path = $uri;
				$variables = '';
			}

			$this->parseUriPath($path);
			$this->parseUriVariables($variables);
		}

		protected function parseUriPath(String $uri)
		{
			if (preg_match('/\//', $uri))
				$this->path = explode('/', substr($uri, 1)	);
			else
				$this->path = $uri;
		}

		protected function parseUriVariables(String $uri)
		{
			if (preg_match('/&/', $uri))
				$vardefs = explode('&', $uri);
			else
				$vardefs = array($uri);

			foreach ($vardefs as $vardef) {
				if (preg_match('/=/', $vardef)) {
					list($key, $value) = explode('=', $vardef);
					$this->variables[$key] = $value;
				}
			}
		}

		public function checkUri(String $uri): bool
		{
			return preg_match($this->uriMask, $uri);
		}


		public function getUri(bool $WithVariables = TRUE): String 
		{
			$returnValue = '';

			if (count($this->path)) {
				foreach($this->path as $file) {
					$returnValue .= '/' . $file;
				}
			} else {
				$returnValue .= '/';
			}

			if ($WithVariables) {
				$returnValue .= '?';
				if (count($this->variables)) {
					$tmpBool = FALSE;
					foreach ($this->variables as $key => $value) {
						if ($tmpBool)
							$returnValue .= '&';
						$returnValue .= $key . '=' . $value;
						$tmpBool = TRUE;
					}
				}
			}
			return $returnValue;
		}

		public function getVariables(): Array
		{
			return $this->variables;
		}
	}
}