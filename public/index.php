<?php

session_start();

// unset($_SESSION['access_token']);

set_include_path("/var/www/googleApi/googlePhpApi/src" . PATH_SEPARATOR. get_include_path());

require_once 'Google/Client.php';
require_once 'Google/Service/Calendar.php';
require_once 'Google/Http/Request.php';


/************************************************
 Die Console mit der Anmeldung und den Attributen 
 ist auf console.developers.google.com/project
************************************************/

$client_id = '749610579744-iqo6ils8432mmv7gbhpsci377v2hbusg.apps.googleusercontent.com';
$client_secret = 'Ke8dXrOtGNjhM6sAJqniY-kp';
$redirect_uri = 'http://googleApi.com';

$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->setAccessType("online"); // Api wird nur dann gebraucht wenn der User im Browser ist
$client->addScope("https://www.googleapis.com/auth/calendar"); // scopes here: developers.google.com/google-apps/calendar/auth


/************************************************
Service muss angemeldet werden
************************************************/
$calendarService = new Google_Service_Calendar($client);


if (isset($_REQUEST['logout'])) {
	unset($_SESSION['access_token']);
}

/************************************************
D.h. Nach Anmeldung bekommen wir den Code und werden 
wieder hierher geleitet. Den Code wird authentifieziert 
und dann access token angefordert.
************************************************/

if (isset ( $_GET ['code'] )) {
	
	$client->authenticate ( $_GET ['code'] ); // Code ok?
	$_SESSION ['access_token'] = $client->getAccessToken (); // Wenn ja dann schick mir access token
	
	//Falls code nicht gesehen werden soll
	//	$redirect = 'http://' . $_SERVER ['HTTP_HOST'];
	// 	header ( 'Location: ' . filter_var ( $redirect, FILTER_SANITIZE_URL ) );
}

/*****************************************************************
 Wenn wir das access token haben, koennen wir 
 Anfragen senden. Ansonsten wird Authentifizierungs-URL erstellt.
*****************************************************************/

if (isset ( $_SESSION ['access_token'] ) && $_SESSION ['access_token']) {
	
	$client->setAccessToken ( $_SESSION ['access_token'] );
	
	if ($client->isAccessTokenExpired ()) {
		unset ( $_SESSION ['access_token'] );
	}
} else {
	$authUrl = $client->createAuthUrl ();
}

/************************************************
 Hat der User eingewilligt, koennen wir eine Liste
 der Events vom Kalender anfordern. 
 Access token wird nochmal gespreichert falls
 waehrend des Vorgangs was schief laeuft.
************************************************/
if ($client->getAccessToken ()) {
	$calendarEvent = $calendarService->events->listEvents('groony14@googlemail.com');
	$_SESSION ['access_token'] = $client->getAccessToken();
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
    	
    	echo "<br><b>-Calendar-</b><br><br>";
    	
    	$calendarItems = $calendarEvent->getItems();
    	var_dump($calendarItems);
//     	var_dump($calendarItems['0']);
//     	var_dump($calendarItems['1']);
//     	var_dump($calendarItems['2']);
//     	var_dump($calendarItems['3']);
		
		for ($i=0; $i <  count($calendarItems); $i++){
			$name = $calendarItems[$i]['summary'];		
			$location =  $calendarItems[$i]['location'];
			$date =  $calendarItems[$i]['modelData']['start']['date'];
			?><table>
			<tr>
				<td><?php echo "|".$date."|" ; //Auf dateTime eingehen?></td>
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

