<?php

require __DIR__.'/../vendor/autoload.php';

$settings = array(
    'consumer_key' => '',
    'consumer_secret' => '',
);


$hashtag = "#twitter";


$tweets = new \TwitterApi\Tweet($settings);
$response = $tweets->getHashtagStatistics($hashtag);

echo $response;
