<?php
require 'EpiCurl.php';
require 'EpiOAuth.php';
require 'EpiTwitter.php';
require 'secret.php';

$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);

echo '<a href="' . $twitterObj->getAuthorizationUrl() . '">Authorize with Twitter</a>';
?>

