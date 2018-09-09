<?php

namespace Routing {
	class HttpRequest {

		protected $uri;
		protected $RequestMethod;

		public function __construct(String $Uri = Null, String $RequestMethod = Null)
		{
			if (is_null($Uri))
				$Uri = $_SERVER['REQUEST_URI'];
			if (is_null($RequestMethod))
				$RequestMethod = $_SERVER['REQUEST_METHOD'];
			
			$this->uri = new Uri($Uri);
			$this->setRequestMethod($RequestMethod);
		}

		public function setRequestMethod(String $RequestMethod): bool
		{
			$AcceptedMethods = array('GET', 'POST', 'DELETE');
			if (in_array(strtoupper($RequestMethod), $AcceptedMethods)) {
				$this->RequestMethod = strtoupper($RequestMethod);
				return TRUE;
			}
			return FALSE;
		}

		public function getVariables(): Array 
		{
			return $this->uri->getVariables();
		}

		public function getUri(): Uri
		{
			return $this->uri;
		}

		public function getRequestMethod(): String
		{
			return $this->RequestMethod;
		}
	}
}