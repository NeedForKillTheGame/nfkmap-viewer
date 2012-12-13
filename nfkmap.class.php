<?php

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
	
	// map palette gd object
	var $palette; 
	
	// map own palette gd object
	var $custom_palette = false;
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
	
		$this->LoadMap();
		
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

	// 040 map version
	function LoadMap()
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
			$tmapobj->lenght = $this->getWord();
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
			$entry->Reserved5 = $this->getWord(true); // transparent color for palette
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
				$this->custom_palette = imagecreatefrombmpstream($pal_bin);

				// set transparent color if enabled
				if ($entry->Reserved6)
				{
					$this->transparent_color = dechex($entry->Reserved5);
					
					// set transparent color to gd object
					$color = hexcoloralloc($this->custom_palette, $this->transparent_color);
					imagecolortransparent($this->custom_palette, $color);
				}
				
			
				if ($this->debug) // (save palette to file)
					file_put_contents("palette_map.bmp", $pal_bin);
				# debug	// TODO: load bmp from castle-ctf? find another code?
				imagebmp($this->custom_palette, "palette_map2.bmp");
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

		// load default palette from the file
		$this->palette = $this->loadPalette("palette_default.png");
		
		$this->drawMap();
		
	}
	
	// open default palette
	function loadPalette($filename)
	{
		$pal = imagecreatefrompng($filename);
		
		// set transparent color
		$color = imagecolorat($pal, 0, 0); // get first pixel color
		imagecolortransparent($pal, $color);

		return $pal;
	}
	
	
	// draw map in $this->image
	function drawMap()
	{
		$width = $this->header->MapSizeX * $this->brick_w;
		$height = $this->header->MapSizeY * $this->brick_h;

		
		// create map layer
		$this->image = imagecreatetruecolor($width, $height);
		$this->fillBackground($this->image, "bg_8.jpg");
		
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
					$im = imagecreatefrompng("portal.png"); // FIXME: take out from the iteration
					imagecopy($this->image, $im, 
						$obj->x * $this->brick_w - $this->brick_w / 2, 
						$obj->y * $this->brick_h - $this->brick_h * 2, 
						0, 
						0, 
						imagesx($im), imagesy($im) );

					// draw arrow to goto position
					arrow($this->image, 
						$obj->x * $this->brick_w + $this->brick_w / 2, // x
						$obj->y * $this->brick_h, // y
						$obj->lenght * $this->brick_w + $this->brick_w / 2, // goto x
						$obj->dir * $this->brick_h, // goto y
						5, 1,
						imagecolorallocatealpha($this->image, 255, 255, 255, 50) );
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
		$pal = $this->palette;

		if ($index >= 54 && $index <= 181)
		{
			if ($this->custom_palette)
			{
				$pal = $this->custom_palette;
				$index -= 54;
			}
			else
				$index -= 7;
		}
	
		// palette size: 8 x 32 bricks
		$x = $index % 8 * $this->brick_w;
		$y = floor($index / 8) * $this->brick_h;
		
		$brick = imagecreatetruecolor($this->brick_w, $this->brick_h); // TODO: black background
		
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
	
	// fill image with repeated background
	function fillBackground(&$im, $filename)
	{
		$bg = imagecreatefromjpeg($filename);
		for ($x = 0; $x < imagesx($im) / imagesx($bg); $x++ )
			for ($y = 0; $y < imagesy($im) / imagesy($bg); $y++ )
				imagecopy($im, $bg, $x * imagesx($bg), $y * imagesy($bg), 0, 0, imagesx($bg), imagesy($bg));
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
		
		if ($this->palette)
			imagedestroy($this->palette);
			
		if ($this->custom_palette)
			imagedestroy($this->custom_palette);
			
		if ($this->image)
			imagedestroy($this->image);
	}
}




// --- DATA TYPES ---


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
	public $x, $y, $lenght, $dir, $wait; // word
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

