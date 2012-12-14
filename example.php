<?php
require_once("nfkmap.class.php");

// very large maps (250x250) can allocate ~265MB of RAM
ini_set('memory_limit', '-1');

$filename = "tourney4.mapa";

try
{
	// load map data into memory
	$nmap = new NFKMap($filename);
	
	$nmap->background = 'bg_8.jpg'; // setup background (if not set then color is black)
	#$nmap->replacefineimages = true; // replace some item images to better quality (armor, quad, etc.)
	#$nmap->drawspecialobjects = true; // draw objects like door triggers, arrows, respawns and empty bricks
	#$nmap->drawlocations = false;	// draw location circles (not good view)
	#$nmap->debug = false; // debug flag will show a lot of uninteresting information
	
	// fill map layer with bricks and objects
	$nmap->DrawMap();
	
	// then save it to an image file
	$nmap->SaveMapImage(false, basename($filename) ); // false = default file name, basename($filename) = map title in thumbnail
	
	// another save examples
	#$nmap->SaveMapImage("mapname.png"); // without thumbnail
	#$nmap->SaveMapImage( "mapname.png", "thumb title" );
	
	// another features
	#$nmap->SaveMap(); // save original map file
	#$nmap->SaveMap("mapname.mapa"); // save original map file
	#echo $nmap-GetHash(); // md5 hash of the map data
	#var_dump($header); // display map basic information (author, name, size, background, gametype)

	// or display image in a browser
	#$nmap->ShowImage();
}
catch(Exception $e)
{
	echo $e->getMessage();
}
