<?php
// war vorher im public ordner

<?php

// see also https://github.com/owncloud/core/issues/7079
// https://github.com/google/google-api-php-client/issues/102  -> download a spreadsheet

// include_once "/var/www/html/TestProject/examples/templates/base.php";
include_once '/var/www/googleApi/php-google-spreadsheet-client-master/src/Google/Spreadsheet/DefaultServiceRequest.php';
include_once '/var/www/googleApi/php-google-spreadsheet-client-master/src/Google/Spreadsheet/ServiceRequestFactory.php';
// include_once '/var/www/html/TestProject/php-google-spreadsheet-client/src/Google/Spreadsheet/ServiceRequestInterface.php';
include_once '/var/www/googleApi/php-google-spreadsheet-client-master/src/Google/Spreadsheet/SpreadsheetService.php';

session_start();

set_include_path("/var/www/googleApi/googlePhpApi/src" . PATH_SEPARATOR ."/var/www/googleApi/php-google-spreadsheet-client-master/src".PATH_SEPARATOR. get_include_path());
require_once 'Google/Client.php';
require_once 'Google/Service/Drive.php';
require_once 'Google/Service/Calendar.php';
require_once 'Google/Http/Request.php';


use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;





/************************************************
 ATTENTION: Fill in these values! Make sure
the redirect URI is to this page, e.g:
http://localhost:8080/user-example.php
************************************************/
$client_id = '749610579744-iqo6ils8432mmv7gbhpsci377v2hbusg.apps.googleusercontent.com';
$client_secret = 'Ke8dXrOtGNjhM6sAJqniY-kp';
$redirect_uri = 'http://googleApi.com';
// $redirect_uri = 'http://hiwi2.jochen-bauer.net';

/************************************************
 Make an API request on behalf of a user. In
this case we need to have a valid OAuth 2.0
token for the user, so we need to send them
through a login flow. To do this we need some
information from our API console project.
************************************************/
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("https://www.googleapis.com/auth/drive https://www.googleapis.com/auth/calendar");

// $client->addScope("https://spreadsheets.google.com/feeds/spreadsheets/private/full");
/************************************************
 We are going to create Drive
service, and query it.
************************************************/
$dr_service = new Google_Service_Drive($client);

//calendar servise

$calendarService = new Google_Service_Calendar($client);

/************************************************
 If we're logging out we just need to clear our
local access token in this case
************************************************/
if (isset($_REQUEST['logout'])) {
	unset($_SESSION['access_token']);
}

/************************************************
 If we have a code back from the OAuth 2.0 flow,
we need to exchange that with the authenticate()
function. We store the resultant access token
bundle in the session, and redirect to ourself.
************************************************/

if (isset ( $_GET ['code'] )) { // = request token

	$client->authenticate ( $_GET ['code'] );
	$_SESSION ['access_token'] = $client->getAccessToken ();

	$redirect = 'http://' . $_SERVER ['HTTP_HOST'];
	header ( 'Location: ' . filter_var ( $redirect, FILTER_SANITIZE_URL ) );
}

//TODO Handle refreshtoken


// if ($client->isAccessTokenExpired()) {
// 	echo 'Access Token Expired';
// 	//$client->authenticate($_GET['code']);
// 	unset($_SESSION['access_token']);

// 	$client->authenticate();
// 	$NewAccessToken['refresh_token'] = $client->getAccessToken();
// 	echo $NewAccessToken['refresh_token']."<br>";
// // 	$NewAccessToken = json_decode($client->getAccessToken());

// 	$client->refreshToken($NewAccessToken['refresh_token']);
// }

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
 If we're signed in, retrieve a list of files from Drive.
************************************************/
if ($client->getAccessToken ()) {
	$_SESSION ['access_token'] = $client->getAccessToken();
	try {
		error_reporting(E_ALL);

		$dr_results = $dr_service->files->listFiles ( array ('maxResults' => 15));



		try {
			$serviceRequest = new DefaultServiceRequest($_SESSION ['access_token']);
			ServiceRequestFactory::setInstance($serviceRequest);
		} catch (Exception $e) {
			echo $e->getMessage();
		}




		// 		$token = $_SESSION['access_token'];
		// 		$serviceRequest = new DefaultServiceRequest( $_SESSION ['access_token'] );
		// 		ServiceRequestFactory::setInstance ( $serviceRequest );
		// 		$spreadsheetService = new SpreadsheetService();
		// 		$spreadsheetFeed = $spreadsheetService->getSpreadsheets();
		// 		var_dump($spreadsheetFeed);

		// 		$spreadsheet = $spreadsheetFeed->getByTitle ( 'Activity Report' );
		// 		var_dump($spreadsheet);

	} catch ( Exception $e ) {
		echo $e->getMessage ();
	}
}

echo header( "User Query - A List Of Files From Drive" );

if ($client_id == '<YOUR_CLIENT_ID>' || $client_secret == '<YOUR_CLIENT_SECRET>' || $redirect_uri == '<YOUR_REDIRECT_URI>') {
	echo missingClientSecretsWarning();
}



/**
 * Download a file's content.
 * From https://developers.google.com/drive/web/manage-downloads
 * @param Google_DriveService $service Drive API service instance.
 * @param File $file Drive File instance.
 * @return String The file's content if successful, null otherwise.
 */
function downloadFile($service, $file, $client) {
	// $downloadUrl = $file->getDownloadUrl ();
	$downloadUrl = $file->getExportLinks ()['text/csv'];

	if ($downloadUrl) {

		try {
			$request = new Google_Http_Request ( $downloadUrl, 'GET', null, null );
				
			$httpRequest = $client->getAuth ()->authenticatedRequest ( $request );
			// veraltet $httpRequest = $client::$io->authenticatedRequest($request);
				
			echo "httpRequest: <br>";
			var_dump ( $httpRequest );
		} catch ( Exception $e ) {
			$e->getMessage ();
		}

		if ($httpRequest->getResponseHttpCode () == 200) {
				
			return $httpRequest->getResponseBody ();
		} else {
			echo "An error occurred.";
			return null;
		}
	} else {
		echo "The file doesn't have any content stored on Drive.";
		return null;
	}
}



/**
 * Print a file's metadata.
 *
 * @param Google_DriveService $service Drive API service instance.
 * @param string $fileId ID of the file to print metadata for.
 */
function printFile($service, $fileId) {
	try {
		$file = $service->files->get ( $fileId );

		print "<b>Title:</b> " . $file->getTitle () . '<br>';
		print "<b>Description: </b> " . $file->getDescription () . '<br>';
		print "<b>MIME type: </b> " . $file->getMimeType () . '<br>';
		print "<b> Id: </b> " . $file->getId () . '<br>';
		print "<b>Download URL: </b> " . $file->getDownloadUrl () . '<br>';
		print "<b>ExportLinks: </b> <br> ";
		var_dump ( $file->getExportLinks () );
	} catch ( Exception $e ) {
		print "An error occurred: " . $e->getMessage ();
	}
}

/**
 *
 * @param  $service is the google service
 * @return list of files in google drive
 */
function retrieveAllFiles($service) {
	$result = array ();
	$pageToken = NULL;

	do {
		try {
			$parameters = array ();
			if ($pageToken) {
				$parameters ['pageToken'] = $pageToken;
			}
			$files = $service->files->listFiles ( $parameters );
				
			$result = array_merge ( $result, $files->getItems () );
			$pageToken = $files->getNextPageToken ();
		} catch ( Exception $e ) {
			print "An error occurred: " . $e->getMessage ();
			$pageToken = NULL;
		}
	} while ( $pageToken );
	return $result;
}

function calendarList($service){


	$calList = $service ->calendarList->listCalendarList();

	return $calList;


}

function spreadSheetApi(){
	try {
		$spreadsheetService = new Google\Spreadsheet\SpreadsheetService ();
		$spreadsheetFeed = $spreadsheetService->getSpreadsheets ();
	} catch ( Exception $e) {
		echo $e->getMessage();
	}

	return $spreadsheetFeed;
}
function changeCSVArray($array) {
	$explodeArray = array ();
	for($pos = 0; $pos < count ( $array ); ++ $pos) {
		$explodeArray [$pos] = explode ( ",", $array [$pos] );
	}

	return $explodeArray;
}

?>
<div class="box">
	<div class="request">
    <?php if (isset($authUrl)) { ?>
      <a class='login' href='<?php echo $authUrl; ?>'>Connect Me!</a>
    <?php } else {
    	
    	echo "<br><b>-Calendar-</b><br>";
    	
    	$calList = calendarList($calendarService);
    	//var_dump($calList['items']['0']);
    	//var_dump($calList);
    	$calendarListEntry = $calendarService->calendarList->get('groony14@googlemail.com');
    	//var_dump($calendarListEntry->getDefaultReminders());
    	
    	
    	$calendarEvent = $calendarService->events->listEvents('groony14@googlemail.com');
    	//var_dump($calendarEvent);

    	$calendarItems = $calendarEvent->getItems();
		var_dump($calendarItems['1']);
    	
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
    	
    	
	 $fileList = retrieveAllFiles ( $dr_service );
	 foreach ( $fileList as $file ) {
		echo "<br>";
		//var_dump($file);
		//echo $title = $file->getTitle()."<br>";
		
		
			
	if ($file->getTitle()=="Activity Report JB") {
		
		echo "<h4>---Print File - Method---</h4>";
		$fileId = $file->getId();
		printFile($dr_service, $fileId);
		
		
		echo "<br>";
		
		echo "<br>";

		echo "<b>---downloadFile - Method---</b> <br> <br>" ;
		echo "<b>->file: </b>".$file->getTitle()."<br>";
		echo "<b>->download url : </b>".$file->getDownloadUrl()."<br>";
		echo "<b>->fileId : </b>".$file->getId()."<br> <br> ";
		$content = downloadFile($dr_service, $file, $client);
		$csvstringToArray = str_getcsv($content,"\n");
		

		
		echo "<br><b>-Explode-</b><br>";
		$preparedArray = changeCSVArray($csvstringToArray);
		
		
		
		//var_dump($preparedArray);
		?><table><?php 
		for($row = 0; $row < count ( $preparedArray ); ++ $row) {
			?><tr><?php 
			for($column = 0; $column < count ( $preparedArray [0] ); ++ $column) {
									
					?><td><?php echo  $preparedArray[$row][$column];?></td><?php 
			}
			?></tr><?php 
		}
		?>
		</table><?php 
		echo "<br>";
	
		
	}

	
	 }
}
?>
</div>
</div>
<?php echo pageFooter(__FILE__);

