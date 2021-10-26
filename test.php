<?php
require('vendor/autoload.php');

use Corbpie\StreamableDl\StreamableDL;


$url = "https://streamable.com/8mr65";//200 link

//$url = "https://streamable.com/ABC123";//404 link

$save_as = "test.mp4";

$sdl = new StreamableDL($url, $save_as);

echo json_encode($sdl->downloadVideo());