<?php

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

namespace MVC {

	class Model {

		/** @ODM\Id */
		private $id;

		public function __get('name')
	}
}