<?php

require_once("nfkmap.class.php");
require_once("bmp.class.php");

$maps_path = "maps\\";
$map_file = "castle-ctf.mapa";

$filename = $maps_path . $map_file;

#try
#{
	$nmap = new NFKMap($filename);
	
	$nmap->SaveMapImage();
	
	echo $nmap->GetHash();
	echo "<br>";
	echo md5( file_get_contents($filename) );
	#$nmap->ShowImage();
#}
#catch(Exception $e)
#{
#	echo $e->getMessage();
#}

