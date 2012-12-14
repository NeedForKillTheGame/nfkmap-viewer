<?php

require_once("nfkmap.class.php");

// very large maps can allocate ~265MB of RAM
ini_set('memory_limit', '-1');

$filename = "maps\\zef3.mapa";

#try
#{
	$nmap = new NFKMap($filename);
	
	$nmap->SaveMapImage(false, true);
	#$nmap->ShowImage(); // display image in browser
#}
#catch(Exception $e)
#{
#	echo $e->getMessage();
#}

added full bmp support of map palettes, fixed palette transparent color, added thumbnail generate (optional)