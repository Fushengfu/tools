<?php
include_once 'vendor/autoload.php';

use Amulet\Http\Request;

$uri = 'http://www.baidu.com';

$result = Request::get($uri);
var_dump($result);