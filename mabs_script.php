<?php
/**
*	maBS PHP Script
*	Version: 1.0.0.6
**/

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$file_temp_name = "sql_log_temp.dat";
$file_last_name = "sql_log.dat";

$sh = fopen($file_temp_name, "w");

$artist;
$song;
$uid;
$rdstitle;
$mediatype;

function init() {
	global $artist, $song, $uid, $mediatype, $rdstitle;
	
	$u = false;
	if (isset($_POST['name1'])) {
		$name1 = $_POST['name1'];
		$artist = str_replace("\"","'", $name1);
		$u = true;
	}
	if (isset($_POST['name2'])) {
		$name2 = $_POST['name2'];
		$song = str_replace("\"","'", $name2);
		$u =  true;
	}
	if (isset($_POST['uid'])) {
		$uid = $_POST['uid'];
		$u =  true;
	}
	if (isset($_POST['rdstitle'])) {
		$rdstitle = $_POST['rdstitle'];
		$u =  true;
	}
	if (isset($_POST['mediatype'])) {
		$mediatype = $_POST['mediatype'];
		$u =  true;
	}

	return $u;
}

function try_connect() {
	global $sh;
	$mysqli = new mysqli('46.148.26.67', 'mabs', 'JsniDq4U', 'mabs');
	if ($mysqli->connect_error) {
		fwrite($sh, "Connection failed: " . $mysqli->connect_error . "\n");
		return null;
	} else {
		fwrite($sh, "Sucessfully Connected!\n");
		return $mysqli;
	}
}

function get_song_id($mysqli) {
	global $sh, $artist, $song, $uid;
	$sql_get_song_id = "SELECT `id` FROM `song_list` WHERE `artist` = '" . $artist . "' AND `song` = '" . $song . "' AND `uid` = " . $uid . ";";
	$res_song_id = $mysqli->query($sql_get_song_id);
	if ($res_song_id->num_rows == 0) {
		fwrite($sh, "Not found song id: " . $song . "\n");
		return 0;
	}
	fwrite($sh, "Found song id: " . $song . "\n");
	return $res_song_id->fetch_array(MYSQLI_ASSOC)['id'];
}

function song_already_exists($mysqli) {
	global $sh, $artist, $song, $uid;
	$sql_contains = "SELECT id FROM song_list WHERE `artist` = '" . $artist . "' AND `song` = '" . $song . "' AND `uid` = " . $uid . ";";
	fwrite($sh, "SQL Song Exists Query: " . $sql_contains . "\n");
	$res_contains = $mysqli->query($sql_contains);
	return $res_contains->num_rows > 0;
}

function add_new_song($mysqli) {
	global $sh, $artist, $song, $uid;	
	$sql_add_song = 'INSERT INTO song_list(`artist`, `song`, `uid`) VALUES ("' . $artist . '", "' . $song . '", ' . $uid . ');';
	fwrite($sh, "Starting of addition of song: " . $song . "\n");
	fwrite($sh,  "SQL Song Adding Query: " . $sql_add_song . "\n");
	$try = 3;
	$found = false;
	while (!$found && $try > 0) {
		if (song_already_exists($mysqli) == false) {
			$try -= 1;
			$res_add_song = $mysqli->query($sql_add_song);
			if ($res_add_song) {
				fwrite($sh, "Added new song: " . $song . "\n");
			} else {
				fwrite($sh, "Error adding new song! Tries remaining: ". $try . "\n");
				fwrite($sh, $mysqli->error . " |\n");
			}
		} else {
			$found = true;
			fwrite($sh, "Song '" . $song . "' found\n");
			$song_id = get_song_id($mysqli);
		}
	}
	if ($try == 0) {
		fwrite($sh, "Tried 0 times to add song without results :(\n");
		return 0;
	} else {
		return $song_id;	
	}
}

function add_to_playlist($song_id, $mysqli) {
	global $sh, $song;
	$sql_add_to_playlist = 'INSERT INTO playlist(`song_id`, `start_time`) VALUES(' . $song_id . ', UTC_TIMESTAMP());';
	$res_add_to_playlist = $mysqli->query($sql_add_to_playlist);
	if ($res_add_to_playlist) {
		fwrite($sh, "Added song to playlist: " . $song_id . ". " . $song . "\n" . $sql_add_to_playlist . "\n");
	} else {
		fwrite($sh, "Error adding song to playlist! \n");
		fwrite($sh, $mysqli->error . " | \n");
	}
}

function try_update_file() {
	global $artist, $song, $uid, $rdstitle, $mediatype;

	$handle1 = fopen("info.dat", "w");
	fwrite($handle1, $artist . "\n" . $song . "\n" . $uid . "\n" . $rdstitle . "\n" . $mediatype. "\n");
	fclose($handle1);
	echo "file 1 updated\n";

	$handle2 = fopen("info2.dat", "w");
	fwrite($handle2, $artist . "|" . $song . "|" . $uid . "|" . $rdstitle . "|" . $mediatype . "|");
	fclose($handle2);
	echo "file 2 updated\n";
}

function try_update_db() {	
	global $sh;
	$time_now = time();
		
	fwrite($sh, "Starting update at " . date('l jS \of F Y h:i:s A', $time_now) . "\n");
	
	$mysqli = try_connect();
	if ($mysqli != null) {
			if (song_already_exists($mysqli)) {
				fwrite($sh, "Song already in DB\n");
				$song_id = get_song_id($mysqli);
			} else {
				$song_id = add_new_song($mysqli);
			}
			if ($song_id != 0) {
				add_to_playlist($song_id, $mysqli);
			}
		$mysqli->close();
		fwrite($sh, "Disconnecting\n");
	} else {
		fwrite($sh, "No MySQLi\n");
	}
}

function save_and_close_log()
{
	global $sh, $file_temp_name, $file_last_name;
	fclose($sh);
    if (trim(file_get_contents($file_temp_name)) != false) {
		unlink($file_last_name);
		copy($file_temp_name, $file_last_name);
	}
}

//  Main Script \/\/\/\/\/

$update = init();
if ($update == true) {
	try_update_file();
	if ($mediatype != 1) {
		fwrite($sh, "Media type wrong [".$mediatype."]. Not saving to db.\n");
		echo "Media type wrong [".$mediatype."]. Not saving to db.\n";
		$update = false;
		$handle3 = fopen("info.dat", "w");
		fwrite($handle3, $artist . "\n" . $song . "\n" . $uid . "\n" . $rdstitle . "\n" . $mediatype. "\n");
		fclose($handle3);
	} else {
		fwrite($sh, "{".$artist . " || " . $song."}\n");
		try {	
			try_update_db();
			echo "DB updated\n";
		} catch (Exception $e) { 
			fwrite($sh, var_dump($e) . "\n");
			echo var_dump($e);
		} 
	}
} else {
	echo "Update is false";
}
save_and_close_log();

$f1 = file_get_contents("http://extrafm:h29zqj@82.135.234.195:8001/proxy.icecast?mount=/extrafm.aac&mode=updinfo&song=".urlencode(file_get_contents('http://www.extrafm.eu/mabs/rds.php')));
$f2 = file_get_contents("http://extrafm:h29zqj@82.135.234.195:8001/proxy.icecast?mount=/extrafm.mp3&mode=updinfo&song=".urlencode(file_get_contents('http://www.extrafm.eu/mabs/rds.php')));
exit;
?>