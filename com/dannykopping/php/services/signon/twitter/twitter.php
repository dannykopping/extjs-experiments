<?php
/* How to use:
 * Add the following line to the top 
 * of any page you wish to protect:
 * 	twitterProtect();
 *
 * Make sure your Twitter app Callback URL points to the page with twitterLogin();
 *
 * CONFIG
 * $consumer_key - Twitter consumer key
 * $consumer_secret - Twitter consumer secret
 * $home - Where you would like to send users upon login
 */
$consumer_key = "dkZPDf2egte9li23BO2Pfw";
$consumer_secret = "SwdjM0eLFaUKLcIbCgMW2Tm9C1A7dUM7h46V2PvDTG8";
$home = "protected.php";

session_start();
require_once 'twitter-async/EpiCurl.php';
require_once 'twitter-async/EpiOAuth.php';
require_once 'twitter-async/EpiTwitter.php';

/* Call this wherever you would like your login link */
function twitterLogin(){
	global $consumer_key, $consumer_secret;
	if (!$consumer_key || !$consumer_secret) die ('Please enter your consumer key/secret!');
	if (isset($_GET['oauth_token'])) twitterCallback();
	$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);
	$url = $twitterObj->getAuthenticateUrl(null, array("oauth_callback" => "http://rsajobs/signon/Twitter-PHP-Login/index.php"));
	// Customise your login link here
	echo "<a href='$url'><img src=\"https://si0.twimg.com/images/dev/buttons/sign-in-with-twitter-l.png\" /></a>";
}


/* Call this function on every page you want protected.
 * If the user is not logged in, the logon link is displayed.
 */
function twitterProtect(){
	if ($_SESSION['logged_in']) return true;
	// Customise error message here
	echo "<p>You must be logged in to view this page!</p>";
	// Display login link for convenience
	twitterLogin();
	exit();
}


/* Process login callback, this can be called from any page proteced by
 * twitterLogin(), the index.php page is recommended though.
 * Once logged in, you are forwarded to the homepage.
 */
function twitterCallback(){
	if (@$_SESSION['logged_in']){ header ('Location: /'); exit(); }
	global $consumer_key, $consumer_secret, $home;
	$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);
	$twitterObj->setToken($_GET['oauth_token']);
	$token = $twitterObj->getAccessToken();

	$twitterObj->setToken($token->oauth_token, $token->oauth_token_secret);
	$twitterInfo= $twitterObj->get_accountVerify_credentials();
	$twitterInfo->response;

	$params = base64_encode("type=twitter&token=".$token->oauth_token."&secret=".$token->oauth_token_secret);

	header("Location: ".ConfigXml::getInstance()->config->paths->{"site-url"}."#/access/signon?$params");
	die();

	/*print_r(array($twitterInfo->name, $_GET['oauth_token'], $token->oauth_token, $token->oauth_token_secret));
	die();
	$username = $twitterInfo->screen_name;
	$_SESSION['logged_in'] = $username;
	// Here you can integrate a database backed login system with stored users and sessions
	header ("Location: $home");
	exit();*/
}


/* Function to log the user out and destroy the session */
function twitterLogout(){
	unset($_SESSION['logged_in']);
	session_destroy();
	// You can either leave the following here or put it on your logout page
	echo "You have logged out, <a href=\"/\">click here</a> to return to the home page.";
	exit();
}

?>
