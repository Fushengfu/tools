<?php
include_once 'vendor/autoload.php';

use Amulet\Http\Request;

$uri = 'http://www.kuaidi100.com/query?type=yuantong&postid=820542942211&temp=0.2702321051340808&phone=';

$result = Request::get($uri, [], [
	'cookie_file'=> 'cookie',
	'referer'=> 'http://www.kuaidi100.com/?from=openv',
]);
$result = Request::get($uri, [], [
	'cookie_file'=> 'cookie',
	'referer'=> 'http://www.kuaidi100.com/?from=openv',
]);
var_dump($result);