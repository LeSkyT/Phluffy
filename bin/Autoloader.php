<?php

class Autoloader {

	protected static $dirs = array();
	protected static $registered = FALSE;
	public static $rootDir = DIRECTORY_SEPARATOR . 'Phluffy';

	public static function init() {
		self::$dirs['Bin'] = $_SERVER['DOCUMENT_ROOT'] . self::$rootDir . DIRECTORY_SEPARATOR . 'bin';
		self::$dirs['Model'] = $_SERVER['DOCUMENT_ROOT'] . self::$rootDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'models';
		self::$dirs['View'] = $_SERVER['DOCUMENT_ROOT'] . self::$rootDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views';
		self::$dirs['Controller'] = $_SERVER['DOCUMENT_ROOT'] . self::$rootDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'controllers';
		self::$dirs['Library'] = $_SERVER['DOCUMENT_ROOT'] . self::$rootDir . DIRECTORY_SEPARATOR . 'lib';

		self::register();
	}

	protected static function register()
	{
		if (!self::$registered){
			spl_autoload_register(__CLASS__ . '::autoload');
			self::$registered = TRUE;
		}
	}


	public static function autoload($className) : bool
	{
		$success = FALSE;

		$fileName = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

		foreach (self::$dirs as $dir) {
			$file = $dir . DIRECTORY_SEPARATOR . $fileName;
			if (self::loadFile($file)){
				$success = TRUE;
				break;
			}
		}

		if (!$success) {
			throw new \Exception('Unable to load ' . $className . '.');
		}

		return $success;
	}

	protected static function loadFile($fileName) : bool
	{
		if (file_exists($fileName)) {
			require_once $fileName;
			return TRUE;
		}
		return FALSE;
	}
}

Autoloader::init();