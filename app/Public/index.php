<?php

require_once '../../bin/Autoloader.php';

$app = Application::start();

echo '<h1>Redirected to app/Public/index.php</h1><br />';


echo '<table border="1">';
echo '<tr><th colspan="2">Routing</th></tr>';
echo '<tr><td>Request URI :</td><td>' . $_SERVER['REQUEST_URI'] . '</td></tr>';
echo '<tr><td>Request Method :</td><td>' . $_SERVER['REQUEST_METHOD'] . '</td></tr>';
$route = $app->route();
echo '<tr><td>Route :</td>';
if (count($route))
	echo '<td><pre>' . json_encode($route) . '</pre></td>';
else
	echo '<td>404 Not found!</td>';
echo '</tr></table>';
