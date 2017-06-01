<?php

require_once 'gapc/src/Google/autoload.php';
require_once ('gapc/src/Google/Client.php');
require_once ('gapc/src/Google/Service/YouTube.php');

header("Access-Control-Allow-Origin: http://extrafm.lt");

header("Content-Time: application/json");

function searchYoutube($artist, $track)
{
	$DEVELOPER_KEY = 'AIzaSyDzVdcc1y0yc_X72IXu2VYZPEdIVMtezdY';
	$client = new Google_Client();
	$client->setDeveloperKey($DEVELOPER_KEY);
	$youtube = new Google_Service_YouTube($client);
	
	try {
    $searchResponse = $youtube->search->listSearch('id,snippet', array(
      'q' => $artist . " " . $track,
      'maxResults' => '48',
    ));

    $videos = '';
    $channels = '';

    foreach ($searchResponse['items'] as $searchResult) {
      switch ($searchResult['id']['kind']) {
        case 'youtube#video':
			return $searchResult['id']['videoId'];
			break;
       }
    }
   } catch (Google_ServiceException $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
	} catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
		htmlspecialchars($e->getMessage()));
  }
	return null;
}
try {
	$sql_sel = "SELECT s.id, s.artist, s.song, s.uid, p.start_time FROM song_list AS s INNER JOIN playlist AS p ON s.id = p.song_id ORDER BY p.id DESC";
	if (isset($_REQUEST['limit'])) {
	$limit = $_REQUEST['limit'];
	} else {
		$limit = 10;
	}
	$sql_sel .= " LIMIT " . $limit;
	$mysqli = new mysqli('46.148.26.67', 'mabs', 'JsniDq4U', 'mabs');
	
	if ($mysqli->connect_error) {
		printf("<p>Connection failed: " . $mysqli->connect_error . "</p>");
	}
	
	$res = $mysqli->query($sql_sel);
	$outputA=array();
	$i = 0;
	while($row = $res->fetch_array(MYSQLI_ASSOC)){
		$row['video_id'] = searchYoutube($row['artist'], $row['song']);
		$dt = new DateTime($row['start_time'], new DateTimeZone('UTC'));
		$dt->setTimezone(new DateTimeZone('Europe/Vilnius'));
		$date = $dt->format('d-m-Y');
		$time = $dt->format('H');
		$row['start_time']=$dt->format('Y-m-d\ H:i:s');

		if(!array_key_exists ($date, $outputA)){
			$outputA[$date]=array();
			$i = 0;
		}
		if(!array_key_exists ($date, $outputA)){
			$outputA[$date][$time.":00"]=array();
			$i = 0;
		}
		$outputA[$date][$time.":00"][$i++] = $row;
	}
	echo json_encode($outputA);

	
}
catch(Exception $e) {
	echo '<p>Ops... Problemo.</p>';
	var_dump($e);
}
