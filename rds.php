<?php	
/**
* Mabs Display RDS script
* Version: 1.0.1.0
**/

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$DEF_NAME = 'EXTRA FM - ALL THE BEST HITS!!';

/*$array = array('ZINIOS', 'REKLAMA', 'AUDIO', 'BIBLIOTEKA', 'LAIDA', 'LAIDOS', 'Traffic block', 'TRAFFIC BLOCK', 'ORAI', 'APZVALGA', 'MINTYS APIE LAIME', 'FONAS', 'VALANDINIS', 'VILNIAUS', 'KOMENTARAS', 'REPORTAZAS', 'KAUNIECIAI', 'BUKIME', 'SVEIKI', 'ON AIR SHOWS', 'NEMIEGOK', 'DAR', 'KLUBAS','ORAI', 'PIRKDAMAS', 'SUZINOK', 'KALBA', '2015', 'ETNORATILAI', 'TEVYNES', 'LABUI', 'EUROPOS', 'LAIKRODIS', 'LIVE', 'KAUNO', 'REGIONO', 'AKTUALIJOS', 'SAVAITE', 'UTENOJE', 'UOSTAMIESCIO', 'KLUBAS', 'POKALBIU', 'PRIES', 'PIRKDAMAS', 'SUZINOK', 'EUROPOS', 'LAIKRODIS', 'LIETUVA', 'PER', 'SAVAITE', '2016', 'BOYBAND', 'GIG', 'GLOSTICK', 'TOP', 'OF', 'HOUR', 'ID', 'SLOGAN', 'RIDO', 'GLOSTIK', 'RAMP', 'TAG2', 'SUPERSHOT', 'TAG1', 'VALANDINIS');	*/

$handle = fopen("info.dat", "r");
$name1 = fgets($handle, 4096);
$name2 = fgets($handle, 4096);
$uid = fgets($handle, 4096);
$rdstitle = fgets($handle, 4096);
$mediatype = fgets($handle, 4096);
fclose($handle);

if ($mediatype != 1) {
	echo $DEF_NAME;
} else {
	$artist = trim(strtoupper($name1));
	$song = trim(strtoupper($name2));
	echo $artist . " - " . $song;
}

?>
