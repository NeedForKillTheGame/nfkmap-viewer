<?php
require_once("nfkmap.class.php");
require_once("mapobj.class.php");

$filename = "test.mapa";

try
{
	$nmap = new NFKMap($filename);
	
	$nmap->Header->MapName = 'Example map created from PHP';
	$nmap->Header->Author = 'HarpyWar';
	$nmap->Header->MapSizeX = 15; // map height (20 by default)
	$nmap->Header->MapSizeY = 8; // map height (30 by default)
	
	// next code fills map border with brick #228
	for ($x = 0; $x < $nmap->Header->MapSizeX; $x++)
		for ($y = 0; $y < $nmap->Header->MapSizeY; $y++)
			if ($x == 0 || $x == $nmap->Header->MapSizeX - 1 || $y == 0 || $y == $nmap->Header->MapSizeY - 1)
				$nmap->Bricks[$x][$y] = 228;
	
	// create respawn on the left corner
	$nmap->Bricks[1][$nmap->Header->MapSizeY - 2] = SimpleObject::Respawn();
	
	// create portal in the right corner, it will teleport player to the left corner
	$obj = SpecialObject::Teleport
	(
		$nmap->Header->MapSizeX - 2, // x
		$nmap->Header->MapSizeY - 2, // y
		2, // goto x
		$nmap->Header->MapSizeY - 2 // goto y
	); 

	$nmap->Objects[] = $obj; // add teleport to the map object list

	
	// save mapa file
	$nmap->SaveMap(); 
	
	
	#echo $nmap->GetHash(); // md5 hash of the map data
	#var_dump($nmap->Header); // display map basic information (author, name, size, background, gametype)
	#var_dump($nmap->Bricks); // display info of bricks on the map
	#var_dump($nmap->Objects); // display info of objects on the map
	#var_dump($nmap->Locations); // display info of locations on the map


	// draw map into memory
	$nmap->DrawMap();
	
	// display image in a browser
	$nmap->ShowImage();
}
catch(Exception $e)
{
	echo $e->getMessage();
}
