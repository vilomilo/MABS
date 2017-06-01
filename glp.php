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
		foreach ($searchResponse['items'] as $searchResult) {
		  switch ($searchResult['id']['kind']) {
			case 'youtube#video':
				return $searchResult['id']['videoId'];
		   }
		}
    } catch (Google_ServiceException $e) {
		$htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
		htmlspecialchars($e->getMessage()));
	} catch (Google_Exception $e) {
		$htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
		htmlspecialchars($e->getMessage()));
	}
	return "";
}

function get_dates($mysqli){
	$sql_dates = "SELECT DISTINCT DATE_FORMAT(start_time, '%Y-%m-%d') AS date FROM playlist";
		$res = $mysqli->query($sql_dates);
		$o = array();
		$i = 0;
		while($row = $res->fetch_array(MYSQLI_ASSOC)){
			$o[$i++] = $row['date'];
		}
		return json_encode($o);
}

function get_hours($mysqli, $date){
	$date_next = clone $date;
	$date_next->add(new DateInterval('P1D'));
	$sql_hours = "SELECT DISTINCT DATE_FORMAT(p.start_time, '%H') AS hour FROM playlist AS p INNER JOIN song_list AS s ON s.id = p.song_id WHERE p.start_time >= '" . $date->format('Y-m-d H:i:s') . "' AND start_time < '" . $date_next->format('Y-m-d H:i:s') . "' ORDER BY start_time ASC";
	$res = $mysqli->query($sql_hours);
	$o = array();
	$i = 0;
	while($row = $res->fetch_array(MYSQLI_ASSOC)){
		$o[$i++] = $row['hour'].":00";
	}
	return json_encode($o);
}

function get_songs_from_date($mysqli, $date){
	$date_next = clone $date;
	$date_next->add(new DateInterval('PT1H'));
	$sql_sel = "SELECT s.artist, s.song, p.start_time FROM playlist AS p INNER JOIN song_list AS s ON s.id = p.song_id WHERE p.start_time >= '" . $date->format('Y-m-d H:i:s') . "' AND p.start_time < '" . $date_next->format('Y-m-d H:i:s') . "' ORDER BY start_time ASC";
	$res = $mysqli->query($sql_sel);
	$outputA=array();
	$i = 0;
	while($row = $res->fetch_array(MYSQLI_ASSOC)){
		$row['video_id'] = searchYoutube($row['artist'], $row['song']);
		$dt = new DateTime($row['start_time'], new DateTimeZone('UTC'));
		$dt->setTimezone(new DateTimeZone('Europe/Vilnius'));
		$row['start_time']=$dt->format('Y-m-d\ H:i:s');
		$date = $dt->format('d-m-Y');
		$time = $dt->format('H');
		if(!array_key_exists ($date, $outputA)){
			$outputA[$date]=array();
			$i = 0;
		}
		if(!array_key_exists ($date, $outputA)){
			$outputA[$date][$time.":00"] = array();
			$i = 0;
		}
		$outputA[$date][$time.":00"][$i++] = $row;
	}
	return json_encode($outputA);
}

try {
	$mysqli = new mysqli('46.148.26.67', 'mabs', 'JsniDq4U', 'mabs');
	if ($mysqli->connect_error) {
		printf("<p>Connection failed: " . $mysqli->connect_error . "</p>");
	}
		
	if(isset($_REQUEST['dates'])) {
		echo get_dates($mysqli);
	} else {
		if (isset($_REQUEST['date'])) {
			$dateR = $_REQUEST['date'];
			if (isset($_REQUEST['time'])) {
				$timeR = $_REQUEST['time'];
				$timeR = (is_numeric($timeR) ? (int)$timeR : 0);
				$timeR = ($timeR > 24 ? 24 : $timeR);
			} else {
				$timeR = 0;
			}
			$d = $dateR." ".$timeR.":00:00";
			$date = new DateTime($d, new DateTimeZone('Europe/Vilnius'));
			if(isset($_REQUEST['hours'])) {
				echo get_hours($mysqli, $date);
			} else {
				$date->setTimezone(new DateTimeZone('UTC'));
				echo get_songs_from_date($mysqli, $date);
			}
		}
	}
}
catch(Exception $e) {
	echo '<p>Ops... Problemo.</p>';
	var_dump($e);
}
