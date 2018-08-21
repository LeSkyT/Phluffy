<?php
require_once 'bin/Autoloader.php';

$uri = $_SERVER['REQUEST_URI'];
$url = '/Phluffy/Test/Lol.fun?var1=test&var2=test2';
print($url.'<br />');

$request = new Routing\Request($url);
