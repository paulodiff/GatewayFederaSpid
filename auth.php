<?php

include('./config/config.php');

require __DIR__ . '/vendor/autoload.php';
use Base64Url\Base64Url;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

// create a log channel
$log = new Logger('gw');
$log->pushHandler(new RotatingFileHandler($LOG_FILE,0,Logger::DEBUG));


if ($DEBUG_GATEWAY) {
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
}


$auth = new OneLogin_Saml2_Auth(); 

$params = array();

$getPar = $_GET; 

if (!is_array($getPar))   die('A_ERROR1');
if (sizeof($getPar) <> 1) die('A_ERROR2');

if ($DEBUG_GATEWAY) { echo "<pre>"; print_r($getPar); }

// get key
$key = current(array_keys($getPar));

if ($DEBUG_GATEWAY) { echo current(array_keys($getPar)); echo "<br>"; echo $getPar[$key]; echo "<br>"; }

$b64_ts_crypted = $getPar[$key];

$ts_crypted_out = Base64Url::decode($b64_ts_crypted);

$crtFile = $CERT_PATH . $key . '.crt';

$fp=fopen($crtFile,"r") or die('A_ERROR: public certificate not found!');
$public_key_string=fread($fp,8192);
fclose($fp);

if(!openssl_public_decrypt($ts_crypted_out, $ts_out, $public_key_string)) die('A_ERROR4');

if ($DEBUG_GATEWAY) { echo $public_key_string; echo "<br>"; echo $ts_out;echo "<br>"; }

$ts_out = $key . ';' . $ts_out;

if ($DEBUG_GATEWAY) { echo $ts_out; echo "<br>"; }

$url = $auth->login($ts_out,$params,false,false,true,true);   // Method that sent the AuthNRequest
$lastRequestID = $auth->getLastRequestID();


$log->info('auth:'. $lastRequestID . ':' . $ts_out);

if ($DEBUG_GATEWAY) { echo $url; echo "<br>"; echo "<a href=\"" . $url . "\">PREPARATO SAML VAI A FEDERA</a>"; }
else {
	header('Pragma: no-cache');
	header('Cache-Control: no-cache, must-revalidate');
	header('Location: ' . $url);
	exit();
}


?>