<?php

// NFK Map Viewer (Need For Kill game map format)
//
// Author:	HarpyWar (harpywar@gmail.com)
// Webpage:	https://github.com/HarpyWar/nfkmap-viewer
// Version:	13.12.2012
// Requirements: PHP >=5.3
class NFKMap
{
	public $filename;
	
	// file handle
	private $f; 
	// current position
	private $pos = 0;

	// header
	private $header = array();
	// bricks
	private $bricks = array();
	// special objects
	private $objects = array();
	

	// map locations
	private $locations = array();
	

	var $res;
	
	// palette transparent color
	var $transparent_color = false;
	
	// final map gd object that can be saved
	var $image; 
	
	// map origin binary stream
	var $stream = '';
	
	// const
	private $brick_w = 32;
	private $brick_h = 16;
	private $location_size = 68; // size of one location
				
	// debug flag will show metadata
	private $debug = true;


	// open file and get handle
	public function __construct($filename)
	{
		if ($this->debug)
			echo "<pre>";
	
		$this->filename = $filename;
		
		if (!$this->handle = @fopen($this->filename, 'r'))
			throw new Exception("Can't open file " . $filename);
	
		
		$this->loadResources();
		
		$this->loadMap();
		
		if ($this->debug)
			print_r($this);
		
	}

	// save map image into png file
	public function SaveMapImage($filename = false)
	{
		if (!$filename)
			$filename = $this->getFileName() . ".png";
			
		
		// TODO: save thumbnail (optional)
	
		imagepng($this->image, $filename);
	}

	// return map image bytes
	public function ShowImage()
	{
		header("Content-Type: image/png");
		imagepng($this->image);
		#return ob_get_contents(); // return bytes
	}	
	
	// return unique md5 hash of the map bytes
	public function GetHash()
	{
		return md5($this->stream);
	}	

	
	// preload image resources
	function loadResources()
	{
		$this->res = new Resources();
		
		$this->res->palette = imagecreatefrompng("data/palette.png");
		// set transparent color
		$color = imagecolorat($this->res->palette, 0, 0); // get first pixel color
		imagecolortransparent($this->res->palette, $color);
		
		$this->res->bg = imagecreatefromjpeg("data/bg_8.jpg");
		$this->res->portal = imagecreatefrompng("data/portal.png");
		$this->res->door = imagecreatefrompng("data/door.png");
		$this->res->button = imagecreatefrompng("data/button.png");
	}
	
	// 040 map version
	function loadMap()
	{
		$this->header = new THeader();
	
		// read header
		$this->header->ID = $this->getString(4);
		if ($this->header->ID != "NMAP" && $this->header->ID != "NDEM")
			throw new Exception($filename . " is not NFK map");

		$this->header->Version = $this->getByte();
		if ($this->header->Version != 3)
			throw new Exception("Incorrect map version");
		
			$this->getByte(); // 0x03 header of string (ignore)
		$this->header->MapName = $this->cutString( $this->getString(70), 0x00 );
			$this->getByte(); // 0x03 header of string (ignore)
		$this->header->Author = $this->cutString( $this->getString(70), 0x00 );
		
		$this->header->MapSizeX = $this->getByte();
		$this->header->MapSizeY = $this->getByte();
		
		$this->header->BG = $this->getByte();
		$this->header->GAMETYPE = $this->getByte();
		$this->header->numobj = $this->getByte();

		$this->header->numlights = $this->getWord();

		if ($this->debug)
			echo "<br>breaks start: " . $this->pos . " "; // 754
		
		// read bricks (start at pos 154)
		for ($y = 0; $y < $this->header->MapSizeY; $y++)
			for ($x = 0; $x < $this->header->MapSizeX; $x++)
				$this->bricks[$x][$y] = $this->getByte();
		
		if ($this->debug)
			echo "<br>objects start: " . $this->pos . " "; // 754
		
		// read objects
		for ($i = 0; $i < $this->header->numobj; $i++)
		{
			$tmapobj = new TMapObj();
			$tmapobj->active = $this->getBool();
				$this->getByte(); // byte alignment (ignore)
				
			$tmapobj->x = $this->getWord();
			$tmapobj->y = $this->getWord();
			$tmapobj->length = $this->getWord();
			$tmapobj->dir = $this->getWord();
			$tmapobj->wait = $this->getWord();
			$tmapobj->targetname = $this->getWord();
			$tmapobj->target = $this->getWord();
			$tmapobj->orient = $this->getWord();
			$tmapobj->nowanim = $this->getWord();
			$tmapobj->special = $this->getWord();
			
			$tmapobj->objtype = $this->getByte();
				$this->getByte(); // byte alignment (ignore)
			
			$this->objects[$i] = $tmapobj;
		}
		// sort objects by objtype descending
		//  it's needed to display button-to-door arrow on front layer,
		//  cause button objtype=2 and door objtype=3
		usort($this->objects, function($a, $b) {
			return !strcmp($a->objtype, $b->objtype);
		});
		


		// read pal and loc blocks
		while ( !feof($this->handle) )
		{
			if ($this->debug)
				echo "<br>entry start: " . $this->pos . " ";
			
			$entry = new TMapEntry();
					
				$this->getByte(); // 0x03 header of string (ignore)
			$entry->EntryType = $this->getString(3);

			$entry->DataSize = $this->getInt(true);
			$entry->Reserved1 = $this->getByte();
			$entry->Reserved2 = $this->getWord();
			$entry->Reserved3 = $this->getInt(true); // FIXME: wrong size?
			$entry->Reserved4 = $this->getInt(true);
			$entry->Reserved5 = $this->getInt(true); // transparent color for palette
			$entry->Reserved6 = $this->getBool(); // transparent value for palette
			
			if ($this->debug)
				print_r($entry);
			
			if ($entry->EntryType == 'pal')
			{
				if ($this->debug)
					echo "<br>pal start: " . $this->pos;
				

				// image binary
				$pal_gzip = $this->getString($entry->DataSize);
				$pal_bin = bzdecompress($pal_gzip);
				
				// create gd object of palette
				$this->res->custom_palette = imagecreatefrombmpstream($pal_bin);

				// set transparent color if enabled
				if ($entry->Reserved6)
				{
					$this->transparent_color = dechex($entry->Reserved5);
					
					// set transparent color to gd object
					$color = hexcoloralloc($this->res->custom_palette, $this->transparent_color);
					imagecolortransparent($this->res->custom_palette, $color);
				}
				
			
				if ($this->debug) // (save palette to file)
					file_put_contents("palette_map.bmp", $pal_bin);
				# debug	// TODO: load bmp from castle-ctf/tourney0/tourney4? find another code?
				imagebmp($this->res->custom_palette, "palette_map2.bmp");
			}
			elseif ($entry->EntryType == 'loc')
			{
				if ($this->debug)
					echo "<br>loc start: " . $this->pos;
			

				// fill locations
				for ($i = 0; $i < $entry->DataSize / $this->location_size; $i++)
				{
					$loc = new TLocationText();
					
					$loc->enabled = $this->getBool();
					$loc->x = $this->getByte();
					$loc->y = $this->getByte();
						$this->getByte(); // 0x0F header of string (ignore)
					$loc->text = $this->cutString( $this->getString(64), 0xF4 );
					
					$this->locations[$i] = $loc;
				}
				
				
				if ($this->debug)
					echo "<br>loc end (file end): " . $this->pos;
			}
			else // end of file
				break;
		}


		$this->drawMap();
		
	}

	
	
	// draw map in $this->image
	function drawMap()
	{
		$width = $this->header->MapSizeX * $this->brick_w;
		$height = $this->header->MapSizeY * $this->brick_h;

		
		// create map layer
		$this->image = imagecreatetruecolor($width, $height);

		
		// fill image with repeated background
		for ($x = 0; $x < imagesx($this->image) / imagesx($this->res->bg); $x++ )
			for ($y = 0; $y < imagesy($this->image) / imagesy($this->res->bg); $y++ )
				imagecopy($this->image, $this->res->bg, $x * imagesx($this->res->bg), $y * imagesy($this->res->bg), 0, 0, imagesx($this->res->bg), imagesy($this->res->bg));
		
		
		
		// draw bricks
		for ($x = 0; $x < $this->header->MapSizeX; $x++)
			for ($y = 0; $y < $this->header->MapSizeY; $y++)
			{
				// pass empty bricks
				if ($this->bricks[$x][$y] == 0)
					continue;
			
				$brick = $this->getBrickImageByIndex($this->bricks[$x][$y]);
				
				// transparent for water(31) and lava(32)
				$transparency = ($this->bricks[$x][$y] == 31 || $this->bricks[$x][$y] == 32) ? 75 : 100;
				
				imagecopymerge($this->image, 
					$brick, $x * $this->brick_w, 
					$y * $this->brick_h, 
					0, 
					0,
					$this->brick_w, $this->brick_h, $transparency);
					
				// fill death place with opacity red color
				if ($this->bricks[$x][$y] == 33)
					imagefilledrectangle($this->image, 
							$x * $this->brick_w, 
							$y * $this->brick_h, 
							$x * $this->brick_w + $this->brick_w - 1, 
							$y * $this->brick_h + $this->brick_h, 
							imagecolorallocatealpha($this->image, 255, 0, 0, 90));
			}
		
		
		// enable antialiasing (smooth teleport lines)
		#imageantialias($this->image, true);
		
		// draw special objects
		foreach ($this->objects as $obj)
		{
			if (!$obj->active)
				continue;
				
			switch ($obj->objtype)
			{
				// teleport
				case 1:
					imagecopy($this->image, $this->res->portal, 
						$obj->x * $this->brick_w - $this->brick_w / 2, 
						$obj->y * $this->brick_h - $this->brick_h * 2, 
						0, 
						0, 
						imagesx($this->res->portal), imagesy($this->res->portal) );

					// draw arrow to goto position
					arrow($this->image, 
						$obj->x * $this->brick_w + $this->brick_w / 2, // x
						$obj->y * $this->brick_h, // y
						$obj->length * $this->brick_w + $this->brick_w / 2, // goto x
						$obj->dir * $this->brick_h, // goto y
						5, 1,
						imagecolorallocatealpha($this->image, 255, 255, 255, 50) );
					break;
					
				// button
				case 2:
					$button_size = 24;
					
					imagecopy($this->image, $this->res->button, 
								$obj->x * $this->brick_w + ($this->brick_w - $button_size) / 2,
								$obj->y * $this->brick_h + ($this->brick_h - $button_size) / 2,
								$obj->orient * $button_size, // offset for button palette
								0,
								$button_size,
								$button_size);
					
					// draw arrows matched to the doors
					foreach ($this->objects as $door_obj)
						if ($door_obj->objtype == 3 && $door_obj->targetname == $obj->target)
							arrow($this->image, 
								$obj->x * $this->brick_w + $this->brick_w / 2, // button center x
								$obj->y * $this->brick_h + $this->brick_h / 2, // button center y
								$door_obj->x * $this->brick_w + (($door_obj->orient == 1) ? $this->brick_w / 2 : $this->brick_w * $door_obj->length / 2), // door center x
								$door_obj->y * $this->brick_h + (($door_obj->orient == 0) ? $this->brick_h / 2 : $this->brick_h * $door_obj->length / 2), // door center y
								5, 1,
								imagecolorallocatealpha($this->image, 76, 255, 0, 50) );
						
					break;
					
				// door
				case 3:
					// clone door image to vertical(0) or horizontal(1) depending $ibj->orient
					for ($i = 0; $i < $obj->length; $i++)
						imagecopy($this->image, $this->res->door, 
							$obj->x * $this->brick_w + (($obj->orient == 1) ? 0 : $this->brick_w * $i),
							$obj->y * $this->brick_h + (($obj->orient == 0) ? 0 : $this->brick_h * $i),
							($obj->orient == 1) ? 0 : $this->brick_w,
							0,
							$this->brick_w,
							$this->brick_h);
					
					break;
					
				// door trigger
				case 4:
					// filled green rectangle
					imagefilledrectangle($this->image, 
						$obj->x * $this->brick_w, 
						$obj->y * $this->brick_h, 
						$obj->x * $this->brick_w + $obj->length * $this->brick_w, 
						$obj->y * $this->brick_h + $obj->dir * $this->brick_h, 
						imagecolorallocatealpha($this->image, 76, 255, 0, 99));
					
					break;
			}
		}
		
		/*
		// draw locations
		foreach ($this->locations as $loc)
		{
			if (!$loc->enabled)
				continue;
		
			imagefilledellipse($this->image, 
				$loc->x * $this->brick_w, // x
				$loc->y * $this->brick_h, // y
				500, 500, // radius
				imagecolorallocatealpha($this->image, 200, 200, 200, 99) );
			
			// TODO: draw $this->text
		}
		*/
		
	}
	
	// return brick image object by it's index in palette
	function getBrickImageByIndex($index)
	{
		$pal = $this->res->palette;

		if ($index >= 54 && $index <= 181)
		{
			if ($this->res->custom_palette)
			{
				$pal = $this->res->custom_palette;
				$index -= 54;
			}
			else
				$index -= 7;
		}
	
		// palette size: 8 x 32 bricks
		$x = $index % 8 * $this->brick_w;
		$y = floor($index / 8) * $this->brick_h;
		
		$brick = imagecreatetruecolor($this->brick_w, $this->brick_h); 
		
		// copy brick from the palette
		imagecopy($brick, $pal, 0, 0, $x, $y, $this->brick_w, $this->brick_h);
		
		// get transparent color of palette
		$color = imagecolortransparent($pal); // FIXME: doesn't work with bmp
		

		
		// set brick transparent color
		imagecolortransparent($brick, $color);
		
		
		#if ($this->debug) // save each brick as single image
		#imagepng($brick, "bricks\\$index.png");
		
		return $brick;
	}
	
	

	// int = 2, longint = 4
	function getInt($long = false)
	{
		$len = !$long ? 2 : 4;
		$data = $this->read($len);

		// convert to int with reading little endian
		$value = unpack("V*", $data);
		
		if ($value)
			return $value[1];
		else
			return 0;
	}
	
	// word = 2, longword = 4
	function getWord($long = false)
	{
		$len = !$long ? 2 : 4;
		$data = $this->read($len);

		// convert to int with reading little endian
		$value = unpack("S*", $data);
		
		if ($value)
			return $value[1] ;
		else
			return 0;
	}
	function getString($len)
	{
		return $this->read($len);
	}
	function getChar()
	{
		return $this->read(1);
	}
	function getByte()
	{
		return ord( $this->getChar() );
	}
	function getBool()
	{
		return $this->getByte() === 1 ? true : false;
	}
	
	// read bytes from current position to specified length
	function read($length)
	{
		fseek($this->handle, $this->pos);
		$data = fread($this->handle, $length);
		
		// increase position
		$this->pos += $length;
		
		// add readed bytes to stream
		$this->stream .= $data;
		
		return $data;
	}

	
	// cut string from start to specified byte
	function cutString($str, $byte = 0x00)
	{
		for ($i = 0; $i < strlen($str); $i++)
		{
			if ( ord($str[$i]) == $byte )
				return substr($str, 0, $i);
		}
		return $str;
	}
		
	// return map name from file name (without extension)
	function getFileName()
	{
		$filename = basename($this->filename);
		return preg_replace("/\\.[^.\\s]{3,4}$/", "", $filename);
	}
	

	function __destruct()
	{
		if ($this->handle)
			fclose($this->handle);
		
		if ($this->res->palette)
			imagedestroy($this->res->palette);
			
		if ($this->res->custom_palette)
			imagedestroy($this->res->custom_palette);
			
		if ($this->image)
			imagedestroy($this->image);
	}
}




// -- DATA TYPES --


// header
class THeader
{
	public $ID = "NMAP"; // char[4]
	public $Version = 3; // byte
	public $MapName = 'test map'; // byte header + string[70]
	public $Author = 'unnamed'; // byte header + string[70]
	public $MapSizeX = 20, $MapSizeY = 30, $BG = 0, $GAMETYPE = 0, $numobj = 0;  // byte
	public $numlights = 0; // word
}
// special object
class TMapObj
{
	public $active; // boolean
	public $x, $y, $length, $dir, $wait; // word
	public $targetname, $target, $orient, $nowanim, $special; // word
	public $objtype; // byte
}

// palette
class TMapEntry
{
	public $EntryType; // byte header + string[3]
	public $DataSize; // longint
	public $Reserved1; // byte
	public $Reserved2; // word
	public $Reserved3; // integer
	public $Reserved4; // longint
	public $Reserved5; // cardinal
	public $Reserved6; // boolean
}

// location
class TLocationText
{
	public $enabled; // boolean
	public $x, $y; // byte
	public $text; // string[64]
}

// image resources
class Resources
{
	public $bg; // background
	public $palette; // default palette
	public $custom_palette; // map palette
	public $portal; // teleport
	public $door; // doors horizontal and vertical
	public $button; // all colored buttons
}





// -- BMP SUPPORT --



// Read 1,4,8,24,32bit BMP files 
// Save 24bit BMP files
// Author: de77
// Licence: MIT
// Webpage: de77.com
// Article about this class: http://de77.com/php/read-and-write-bmp-in-php-imagecreatefrombmp-imagebmp
// First-version: 07.02.2010
// Version: 21.08.2010
class BMP
{
	public static function imagebmp(&$img, $filename = false)
	{
		$wid = imagesx($img);
		$hei = imagesy($img);
		$wid_pad = str_pad('', $wid % 4, "\0");
		
		$size = 54 + ($wid + $wid_pad) * $hei * 3; //fixed
		
		//prepare & save header
		$header['identifier']		= 'BM';
		$header['file_size']		= self::dword($size);
		$header['reserved']			= self::dword(0);
		$header['bitmap_data']		= self::dword(54);
		$header['header_size']		= self::dword(40);
		$header['width']			= self::dword($wid);
		$header['height']			= self::dword($hei);
		$header['planes']			= self::word(1);
		$header['bits_per_pixel']	= self::word(24);
		$header['compression']		= self::dword(0);
		$header['data_size']		= self::dword(0);
		$header['h_resolution']		= self::dword(0);
		$header['v_resolution']		= self::dword(0);
		$header['colors']			= self::dword(0);
		$header['important_colors']	= self::dword(0);
	
		if ($filename)
		{
		    $f = fopen($filename, "wb");
		    foreach ($header AS $h)
		    {
		    	fwrite($f, $h);
		    }
		    
			//save pixels
			for ($y=$hei-1; $y>=0; $y--)
			{
				for ($x=0; $x<$wid; $x++)
				{
					$rgb = imagecolorat($img, $x, $y);
					fwrite($f, self::byte3($rgb));
				}
				fwrite($f, $wid_pad);
			}
			fclose($f);
		}
		else
		{
		    foreach ($header AS $h)
		    {
		    	echo $h;
		    }
		    
			//save pixels
			for ($y=$hei-1; $y>=0; $y--)
			{
				for ($x=0; $x<$wid; $x++)
				{
					$rgb = imagecolorat($img, $x, $y);
					echo self::byte3($rgb);
				}
				echo $wid_pad;
			}
		}	
	}
	
	public static function imagecreatefrombmp($filename)
	{
		$file = fopen($filename, "rb");
		$read = fread($file, 10);
		while (!feof($file) && ($read <> ""))
			$read .= fread($file, 1024);
			
		return imagecreatefrombmpstream($read);
	}
	
	// http://www.craiglotter.co.za/2011/05/06/php-create-image-object-from-bmp-file-with-imagecreatefrombmp-function/
	public static function imagecreatefrombmpstream($stream) 
	{
		$temp = unpack("H*", $stream);
		$hex = $temp[1];
		$header = substr($hex, 0, 108);
		if (substr($header, 0, 4) == "424d") {
			$header_parts = str_split($header, 2);
			$width = hexdec($header_parts[19] . $header_parts[18]);
			$height = hexdec($header_parts[23] . $header_parts[22]);
			unset($header_parts);
		}
		$x = 0;
		$y = 1;
		$img = imagecreatetruecolor($width, $height);
		$body = substr($hex, 108);
		$body_size = (strlen($body) / 2);
		$header_size = ($width * $height);
		$usePadding = ($body_size > ($header_size * 3) + 4);
		for ($i = 0; $i < $body_size; $i+=3) {
			if ($x >= $width) {
				if ($usePadding)
					$i += $width % 4;
				$x = 0;
				$y++;
				if ($y > $height)
					break;
			}
			$i_pos = $i * 2;
			$r = hexdec($body[$i_pos + 4] . $body[$i_pos + 5]);
			$g = hexdec($body[$i_pos + 2] . $body[$i_pos + 3]);
			$b = hexdec($body[$i_pos] . $body[$i_pos + 1]);
			$color = imagecolorallocate($img, $r, $g, $b);
			imagesetpixel($img, $x, $height - $y, $color);
			$x++;
		}
		unset($body);
		return $img;
	}

	private static function byte3($n)
	{
		return chr($n & 255) . chr(($n >> 8) & 255) . chr(($n >> 16) & 255);	
	}
	
	private static function undword($n)
	{
		$r = unpack("V", $n);
		return $r[1];
	}
	
	private static function dword($n)
	{
		return pack("V", $n);
	}
	
	private static function word($n)
	{
		return pack("v", $n);
	}
}

function imagebmp(&$img, $filename = false)
{
	return BMP::imagebmp($img, $filename);
}

function imagecreatefrombmp($filename)
{
	return BMP::imagecreatefrombmp($filename);    
}	
function imagecreatefrombmpstream($stream)
{
	return BMP::imagecreatefrombmpstream($stream);    
}

function hexcoloralloc($im, $hex)
{ 
  $a = hexdec(substr($hex,0,2)); 
  $b = hexdec(substr($hex,2,2)); 
  $c = hexdec(substr($hex,4,2)); 

  return imagecolorallocate($im, $a, $b, $c); 
} 
// draw arrow
function arrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color) {
    $distance = sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));

    $dx = $x2 + ($x1 - $x2) * $alength / $distance;
    $dy = $y2 + ($y1 - $y2) * $alength / $distance;

    $k = $awidth / $alength;

    $x2o = $x2 - $dx;
    $y2o = $dy - $y2;

    $x3 = $y2o * $k + $dx;
    $y3 = $x2o * $k + $dy;

    $x4 = $dx - $y2o * $k;
    $y4 = $dy - $x2o * $k;

    imageline($im, $x1, $y1, $dx, $dy, $color);
    imagefilledpolygon($im, array($x2, $y2, $x3, $y3, $x4, $y4), 3, $color);
}