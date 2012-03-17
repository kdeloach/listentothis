<?php

error_reporting(E_ALL | E_STRICT);
ini_set('xdebug.show_local_vars', 1);

$app_path = 'C:\wamp\www\listentothis\app';
$lib_path = 'C:\wamp\www\kevinx.net\labs\php\kwork';

set_include_path(get_include_path() . PATH_SEPARATOR . $lib_path);

require 'AppMain.php';
AppMain::handleRequest($app_path);
