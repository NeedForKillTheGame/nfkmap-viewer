<?php

require_once("nfkmap.class.php");

$filename = "maps\\zef3.mapa";

#try
#{
	$nmap = new NFKMap($filename);
	
	$nmap->SaveMapImage();
	#$nmap->ShowImage(); // display image in browser
#}
#catch(Exception $e)
#{
#	echo $e->getMessage();
#}

