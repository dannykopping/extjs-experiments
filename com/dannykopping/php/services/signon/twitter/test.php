<?php

$consumer_key = "dkZPDf2egte9li23BO2Pfw";
$consumer_secret = "SwdjM0eLFaUKLcIbCgMW2Tm9C1A7dUM7h46V2PvDTG8";

$access_token = "81819239-pPAeMrZRIjexkjEZK0baLsiWH0muZld4JYt8UCjbF";
$secret = "1hG9dkCiezTbndIclMSEVl36yiE3wXQsdyOxW23s";

require_once 'twitter-async/EpiCurl.php';
require_once 'twitter-async/EpiOAuth.php';
require_once 'twitter-async/EpiTwitter.php';

$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);
$twitterObj->setToken($access_token, $secret);

$twitterInfo = $twitterObj->get_accountVerify_credentials();

$twitterInfo->response;
print_r(array($twitterInfo->name));
die();
?>