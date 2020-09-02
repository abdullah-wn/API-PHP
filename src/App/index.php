<?php

use App\Utils\Cache;

include __DIR__.'/../../vendor/autoload.php';
ini_set('display_errors', E_ERROR);

$start = new DateTime();


$end = new DateTime();
$time = $end->getTimestamp() - $start->getTimestamp();
echo "$time s";
/**
$cache = new Cache(__DIR__.'/../cache.xml');
$cache
->set('first_key', '50')
->set('second_key', '10')
->set('third_key', '10');
echo "{$cache->getXml()->asXml()}\n";
echo "{$cache->persist()}\n";
**/