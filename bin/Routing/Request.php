<?php

namespace Routing {
	class Request {

		protected $_data = array();

		public function __construct($url) {
			$uriPattern = '/^\/((([A-Za-z0-9-_%.]+)\/)*)([A-Za-z0-9-_%.]+)?(\?([A-Za-z0-9-_%.]+(=[A-Za-z0-9-_%.]+)?(&[A-Za-z0-9-_%.]+(=[A-Za-z0-9-_%.]+)?)*))?$/';

			if (preg_match($uriPattern, $url, $matches)){
				$this->_data['Path'] = $this->parseFolders(substr($matches[1], 0, strlen($matches[1]) - 1));
				$this->_data['Path'][] = $matches[4];
				$this->_data['Variables'] = $this->parseVariables($matches[6]);

				print('<br /><hr />');
				print('<pre>');
				print_r($this->_data);
				print('</pre>');

			} else {
				throw new \Exception('URL Format Error: "' . $url . '".');
			}
		}

		protected function parseFolders(String $str): Array
		{
			return explode('/', $str);
		}

		protected function parseVariables(String $str): Array
		{
			$retval = array();
			$tmp = explode('&', $str);
			foreach ($tmp as $vardef) {
				$def = explode('=', $vardef);
				$retval[$def[0]] = $def[1];
			}

			return $retval;
		}
	}
}