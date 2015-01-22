<?php

// see google doc: developers.google.com/google-apps/calendar/v3/reference/events
//some change
session_start();

// unset($_SESSION['access_token']);

set_include_path("/var/www/googleApi/googlePhpApi/src" . PATH_SEPARATOR. get_include_path());

require_once 'Google/Client.php';
require_once 'Google/Service/Drive.php';
require_once 'Google/Service/Calendar.php';
require_once 'Google/Http/Request.php';

/**
 * Attribute
 * 
 * Die Console mit der Anmeldung und den Attributen ist auf https://console.developers.google.com/project
 */

$client_id = '749610579744-iqo6ils8432mmv7gbhpsci377v2hbusg.apps.googleusercontent.com';
$client_secret = 'Ke8dXrOtGNjhM6sAJqniY-kp';
 $redirect_uri = 'http://googleApi.com';
// $redirect_uri = 'http://hiwi2.jochen-bauer.net';

$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->setAccessType("offline");
$client->addScope("https://www.googleapis.com/auth/calendar");


/**
 * Service muss angemeldet werden
 */
$calendarService = new Google_Service_Calendar($client);


if (isset($_REQUEST['logout'])) {
	unset($_SESSION['access_token']);
}

/************************************************
D.h. Nach Anmeldung bekommen wir den Code und werden 
wieder hierher geleitet. Den Code müssen wir auslesen 
und gegen access token austauschen.
************************************************/

if (isset ( $_GET ['code'] )) {
	
	$client->authenticate ( $_GET ['code'] );
	$_SESSION ['access_token'] = $client->getAccessToken ();
	
	$redirect = 'http://' . $_SERVER ['HTTP_HOST'];
	header ( 'Location: ' . filter_var ( $redirect, FILTER_SANITIZE_URL ) );
}

/************************************************
 If we have an access token, we can make
requests, else we generate an authentication URL.
************************************************/

if (isset ( $_SESSION ['access_token'] ) && $_SESSION ['access_token']) {
	
	$client->setAccessToken ( $_SESSION ['access_token'] );
	
	if ($client->isAccessTokenExpired ()) {
		unset ( $_SESSION ['access_token'] );
	}
} else {
	$authUrl = $client->createAuthUrl ();
}

/************************************************
 If we're signed in, retrieve a list of events from Calendar.
************************************************/
if ($client->getAccessToken ()) {
	$_SESSION ['access_token'] = $client->getAccessToken();
	
	$calendarEvent = $calendarService->events->listEvents('groony14@googlemail.com');
}

if ($client_id == '<YOUR_CLIENT_ID>' || $client_secret == '<YOUR_CLIENT_SECRET>' || $redirect_uri == '<YOUR_REDIRECT_URI>') {
			echo missingClientSecretsWarning();
	}
		
?>
<div class="box">
	<div class="request">
    <?php if (isset($authUrl)) { ?>
      <a class='login' href='<?php echo $authUrl; ?>'>Connect Me!</a>
    <?php } else {
    	
    	echo "<b>-Access Token-</b><br><br>";
		var_dump($_SESSION['access_token']);
    	
    	echo "<br><b>-Today-</b><br><br>";
    	
    	$startTime = new \DateTime();
    	$startTime->add(\DateInterval::createFromDateString("today"));
    	$startTime = $startTime->format('Y-m-d');
 		var_dump($startTime) ;   
    	
    	echo "<br><b>-Calendar-</b><br><br>";
    	
    	$calendarItems = $calendarEvent->getItems();
    	var_dump($calendarItems['0']);
    	var_dump($calendarItems['1']);
    	var_dump($calendarItems['2']);
		
		for ($i=0; $i <  count($calendarItems); $i++){
			$name = $calendarItems[$i]['summary'];		
			$location =  $calendarItems[$i]['location'];
			$date =  $calendarItems[$i]['modelData']['start']['date'];
			?><table>
			<tr>
				<td><?php echo "|".$date."|" ;?></td>
				<td><?php echo $name. "|" ;?></td>
				<td><?php echo $location."|";?></td>
			</tr>
		</table><?php 
        }
		
    	echo "<br>"; 
}
?>
</div>
</div>

