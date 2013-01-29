<?php
// create and save thumbnail image of map

// if you use a composer then just include('vendor/autoload.php');
include("../lib/autoloader.php");
Autoloader::register();


use NFK\MapViewer\MapViewer;

// PHP GD can allocate ~256MB of RAM for when drawing a very large map (250x250)
ini_set('memory_limit', '-1');


$filename = "data/pro-dm0.mapa";

// create map object
$nmap = new MapViewer($filename);

// load map data in memory
$nmap->LoadMap();

// draw map in memory
$im = $nmap->DrawMap();


// get file md5
$filename = md5( file_get_contents($filename) );

// text on image
$title = sprintf("%s (%sx%s)", $nmap->GetFileName(), $nmap->Header->MapSizeX, $nmap->Header->MapSizeY);

// create resized image with max size 300px and text
$im2 = resizeImage($im, 300, $title);

// save jpg image with 75 quality
imagejpeg( $im2, $filename . '_thumb.jpg', 75);











// return resized image
function resizeImage($src, $max_size = 200, $text = false)
{
	list($tn_width, $tn_height) = getpropsize(imagesx($src), imagesy($src), $max_size);
	
	
	$im=imagecreatetruecolor($tn_width,$tn_height);
	imagecopyresampled($im,$src,0,0,0,0,$tn_width, $tn_height,imagesx($src), imagesy($src));
	
	// text
	if ($text)
	{
		// black
		$bar_color = imagecolorallocatealpha($im, 0, 0, 0, 80);
		
		imagefilledrectangle($im, 0, $tn_height-20, $tn_width, $tn_height, $bar_color);
		
		$txt_color = imagecolorallocate($im, 255, 255, 255);
		$txt_file = "data/arial.ttf";
		if (!file_exists($txt_file))
			die('can\'t find font arial.ttf');
			
		$txt_fontsize = 10.5;

		imagettftext ($im, $txt_fontsize, 0,  10, $tn_height-6, $txt_color, $txt_file, $text);
	}

	return $im;
}

// return proportional small size from the source size
function getpropsize($width, $height, $max)
{
	if ($width <= $max and $height <= $max)
		return array($width, $height);
		
	$lager = ($width > $height) ? $width : $height; //  сторона, которая длиннее
	
	$k = $lager / $max; // во сколько раз уменьшить

	$w = @round($width / $k); // 1%
	$h = @round($height / $k); // 1%

	return array($w, $h);
}
