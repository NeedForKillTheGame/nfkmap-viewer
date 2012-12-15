<?php
require_once("nfkmap.class.php");

// PHP can allocate ~265MB of RAM for when drawing a very large map (250x250) 
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

	// fill map layer with bricks and objects
	#$nmap->LoadMap();
	

	// place own bricks and objects to the map
	/*
	for ($x = 0; $x < $nmap->Header->MapSizeX; $x++)
		for ($y = 0; $y < $nmap->Header->MapSizeY; $y++)
			if ($x == 0 || $x == $nmap->Header->MapSizeX - 1 || $y == 0 || $y == $nmap->Header->MapSizeY - 1)
				$nmap->Bricks[$x][$y] = 228;
	
	// create respawn on the left corner
	$nmap->Bricks[1][$nmap->Header->MapSizeY - 2] = 34;
	
	// create teleport on the right corner, it will teleport player to the left corner
	$obj = new TMapObj();
	$obj->active = 1;
	$obj->x = $nmap->Header->MapSizeX - 2; // x
	$obj->y = $nmap->Header->MapSizeY - 2; // y
	$obj->length = 2; // goto x
	$obj->dir = $nmap->Header->MapSizeY - 2; // goto y
	$obj->objtype = 1; // 1 = teleport
	
	$nmap->Objects[] = $obj; // add teleport object to list
	*/
	
	// draw map in memory
	$nmap->DrawMap();
	
	// then save it to an image file
	$nmap->SaveMapImage(false, basename($filename) ); // false = default file name, basename($filename) = map title in thumbnail
	
	// another save examples
	#$nmap->SaveMapImage("mapname.png"); // without thumbnail
	#$nmap->SaveMapImage( "mapname.png", "thumb title" );
	
	// another features
	#$nmap->SaveMap(); // save mapa file
	#$nmap->SaveMap("mapname.mapa"); // save mapa file with custom filename
	#echo $nmap-GetHash(); // md5 hash of the map data
	#var_dump($header); // display map basic information (author, name, size, background, gametype)

	// or display image in a browser
	#$nmap->ShowImage();
}
catch(Exception $e)
{
	echo $e->getMessage();
}
