<?php

// ----------------------------------------------------------
// NFK Map Viewer (Need For Kill game map format)
//
// Author: HarpyWar (harpywar@gmail.com)
// Webpage: http://harpywar.com
// Project page: https://github.com/HarpyWar/nfkmap-viewer
// Version: 15.12.2012
// Requirements: PHP >=5.3 with enabled extensions: php_gd2, php_bz2
// ----------------------------------------------------------
class NFKMap
{
	/* --- SETUP START --- */
	
	// fill map background with repeated image
	//  value is a number of the file "data/bg_[number].jpg"
	//  if value == 0, then fill background with black color
	public $background = false;
	
	// replace some item images to better quality (armor, quad, etc.)
	public $replacefineimages = true;
	
	// draw location circles (not good view)
	public $drawlocations = false;
	
	// draw objects like door triggers, arrows, respawns and empty bricks
	public $drawspecialobjects = true;	

	/* --- SETUP END --- */
	
	
	
	
	
	// header (map info)
	public $Header;
	// bricks
	public $Bricks = array();
	// special objects
	public $Objects = array();
	// map locations
	public $Locations = array();
	
	
	
	
	// debug flag will show metadata
	private $debug = false;
	
	private $filename = 'new.mapa';
	
	// file handle
	private $handle; 
	// current position
	private $pos = 0;

	// resources
	private $imres;
	
	// palette transparent color
	private $transparent_color = false;
	
	// final map gd object that can be saved
	private $image; 
	
	// map origin binary stream
	private $stream = '';
	
	
	
	// const
	private $brick_w = 32;
	private $brick_h = 16;
	private $tlocation_size = 68; // size in bytes of one TLocationText structure


	// load and parse map stream
	public function __construct($filename)
	{
		if ($this->debug)
			echo "<pre>";
	
		$this->filename = $filename;
		
		// initial empty map
		$this->Header = new THeader();
	}

	// save map image into png file
	// $thumbnail - map title (if exist then create thumbnail file)
	// $thumb_size - max size of thumbnail
	public function SaveMapImage($filename = false, $thumbnail = false, $thumb_size = 350)
	{
		if (!$filename)
			$filename = $this->getFileName();
			
		if (!$this->image)
			throw new Exception('Image is empty. You have to call DrawMap() method before.');

		imagepng($this->image, $filename . ".png");
		
		if ($thumbnail)
		{
			$title = sprintf("%s (%sx%s)", $this->getFileName($thumbnail), $this->Header->MapSizeX, $this->Header->MapSizeY);
			$im = resizeImage($this->image, $thumb_size, $title);
			imagejpeg( $im, $filename . "_thumb.jpg", 75);
		}
	}

	// return map image bytes
	public function ShowImage()
	{
		if (!$this->image)
			throw new Exception('Image is empty. You have to call DrawMap() method before.');
	
		header('Content-Type: image/png; filename="' . $this->Header->MapName . '"');
		
		imagepng($this->image);
		#return ob_get_contents(); // return bytes
	}	
	
	// return unique md5 hash of the map bytes
	public function GetHash()
	{
		return md5($this->stream);
	}

	
	
	// save map stream into file
	public function SaveMap($filename = false)
	{
		// clear stream
		$this->stream = '';

		// write header
			$this->Header->ID = 'NMAP';
		$this->putString($this->Header->ID);
			$this->Header->Version = 3;
		$this->putByte($this->Header->Version);
		
			$this->putByte(0x03); // header of string
		$this->putString($this->Header->MapName . chr(0x00), 70);
			$this->putByte(0x03); // header of string
		$this->putString($this->Header->Author . chr(0x00), 70);
		
		$this->putByte($this->Header->MapSizeX);
		$this->putByte($this->Header->MapSizeY);
		
		$this->putByte($this->Header->BG);
		$this->putByte($this->Header->GAMETYPE);
			$this->Header->numobj = count($this->Objects);
		$this->putByte($this->Header->numobj);

		$this->putWord($this->Header->numlights);
		
		
		// write bricks
		for ($y = 0; $y < $this->Header->MapSizeY; $y++)
			for ($x = 0; $x < $this->Header->MapSizeX; $x++)
				$this->putByte( isset($this->Bricks[$x][$y]) ? $this->Bricks[$x][$y] : 0 );
		
		// write objects
		foreach($this->Objects as $tmapobj)
		{
			$this->putBool($tmapobj->active);
				$this->putByte(0x03); // byte alignment
				
			$this->putWord($tmapobj->x);
			$this->putWord($tmapobj->y);
			$this->putWord($tmapobj->length);
			$this->putWord($tmapobj->dir);
			$this->putWord($tmapobj->wait);
			$this->putWord($tmapobj->targetname);
			$this->putWord($tmapobj->target);
			$this->putWord($tmapobj->orient);
			$this->putWord($tmapobj->nowanim);
			$this->putWord($tmapobj->special);
			
			$this->putByte($tmapobj->objtype);
				$this->putByte(0x03); // byte alignment
		}
		
		// write map palette
		if ( $this->imres && isset($this->imres['custom_palette']) )
		{
			imagebmp($this->imres['custom_palette']);
			$pal_bin = imagebmp($this->imres['custom_palette']);
			$pal_bz = bzcompress($pal_bin);
			
			// entry
			$this->putByte(0x03); // 0x03 header of string
			$this->putString('pal'); 
			
			$this->putInt(strlen($pal_bz), true); // bmp size
			$this->putByte(0);
			$this->putWord(0);
			$this->putInt(0);
			$this->putInt(0);
			$this->putInt($this->transparent_color ? hexdec($this->transparent_color) : 0); // transparent color for palette
			$this->putBool($this->transparent_color ? true : false); // transparent value for palette
			
			$this->putString($pal_bz); 
		}

		// write locations
		if ($this->Locations && count($this->Locations) > 0)
		{
				// entry
				$this->putByte(0x03); // 0x03 header of string
				$this->putString('loc'); 
				
				$this->putInt(count($this->Locations) * $this->tlocation_size, true); // locations size
				$this->putByte(0);
				$this->putWord(0);
				$this->putInt(0);
				$this->putInt(0);
				$this->putInt(0);
				$this->putBool(false);
				
				foreach($this->Locations as $loc)
				{
					$this->putBool($loc->enabled);
					$this->putByte($loc->x);
					$this->putByte($loc->y);
						$this->putByte(0x0F); // 0x0F header of string
					$this->putString($loc->text . chr(0xF4) . chr(0x39), 64);
				}
		}
		
		// write data to file
		if (!$filename)
			$filename = $this->getFileName();
	
		file_put_contents($filename . '.mapa', $this->stream);
	}

	
	// 040 map version
	public function LoadMap()
	{
		if (!$this->handle = @fopen($this->filename, 'r'))
			throw new Exception("Can't open file " . $this->filename);
			

			
		$this->Header = new THeader();
	
		// read header
		$this->Header->ID = $this->getString(4);
		if ($this->Header->ID != "NMAP")
			throw new Exception($this->filename . " is not NFK map");

		$this->Header->Version = $this->getByte();
		if ($this->Header->Version != 3)
			throw new Exception("Incorrect map version");
		
			$this->getByte(); // 0x03 header of string (ignore)
		$this->Header->MapName = $this->cutString( $this->getString(70), 0x00 );
			$this->getByte(); // 0x03 header of string (ignore)
		$this->Header->Author = $this->cutString( $this->getString(70), 0x00 );
		
		$this->Header->MapSizeX = $this->getByte();
		$this->Header->MapSizeY = $this->getByte();
		
		$this->Header->BG = $this->getByte();
		$this->Header->GAMETYPE = $this->getByte();
		$this->Header->numobj = $this->getByte();

		$this->Header->numlights = $this->getWord();

		if ($this->debug)
			echo "<br>breaks start: " . $this->pos . " "; // 754
		
		// read bricks (start at pos 154)
		for ($y = 0; $y < $this->Header->MapSizeY; $y++)
			for ($x = 0; $x < $this->Header->MapSizeX; $x++)
				$this->Bricks[$x][$y] = $this->getByte();
		
		if ($this->debug)
			echo "<br>objects start: " . $this->pos . " "; // 754
		
		// read objects
		for ($i = 0; $i < $this->Header->numobj; $i++)
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
			
			$this->Objects[$i] = $tmapobj;
		}

		
		// read pal and loc blocks
		while ( !feof($this->handle) )
		{
			if ($this->debug)
				echo "<br>entry start: " . $this->pos . " ";
			
			$entry = new TMapEntry();
					
				$this->getByte(); // 0x03 header of string (ignore)
			$entry->EntryType = $this->getString(3);

			$entry->DataSize = $this->getInt();
			$entry->Reserved1 = $this->getByte();
			$entry->Reserved2 = $this->getWord();
			$entry->Reserved3 = $this->getInt();
			$entry->Reserved4 = $this->getInt();
			$entry->Reserved5 = $this->getInt(); // transparent color for palette
			$entry->Reserved6 = $this->getBool(); // transparent value for palette
			
			if ($this->debug)
				print_r($entry);
			
			if ($entry->EntryType == 'pal')
			{
				if ($this->debug)
					echo "<br>pal start: " . $this->pos;
				

				// image binary
				$pal_bz = $this->getString($entry->DataSize);
				$pal_bin = bzdecompress($pal_bz);
				
				// create gd object of palette
				$this->imres['custom_palette'] = imagecreatefrombmpstream($pal_bin);
				
				// set transparent color if enabled
				if ($entry->Reserved6)
				{
					$this->transparent_color = inverseHex( dechex($entry->Reserved5) );
					
					// set transparent color to gd object
					$color = hexcoloralloc($this->imres['custom_palette'], $this->transparent_color);
					imagecolortransparent($this->imres['custom_palette'], $color);
				}
				
			
				if ($this->debug) // (save palette to file)
				{
					file_put_contents("palette_map.bmp", $pal_bin); // original
					imagepng($this->imres['custom_palette'], "palette_map.png"); // handled
				}
			}
			elseif ($entry->EntryType == 'loc')
			{
				if ($this->debug)
					echo "<br>loc start: " . $this->pos;
			

				// fill locations
				for ($i = 0; $i < $entry->DataSize / $this->tlocation_size; $i++)
				{
					$loc = new TLocationText();
					
					$loc->enabled = $this->getBool();
					$loc->x = $this->getByte();
					$loc->y = $this->getByte();
						$this->getByte(); // 0x0F header of string (ignore)
					$loc->text = $this->cutString( $this->getString(64), 0xF4 );
					
					$this->Locations[$i] = $loc;
				}
				
				
				if ($this->debug)
					echo "<br>loc end (file end): " . $this->pos;
			}
			else // end of file
				break;
		}
		
		
		if ($this->debug)
			print_r($this);
			
		return $this;
	}

	
	// draw map to $this->image
	function DrawMap()
	{
		// load image resources
		$this->loadResources();
	
		$width = $this->Header->MapSizeX * $this->brick_w;
		$height = $this->Header->MapSizeY * $this->brick_h;


		// create map layer
		$this->image = imagecreatetruecolor($width, $height);

		
		// fill image with repeated background
		if ($this->background)
			for ($x = 0; $x < imagesx($this->image) / imagesx($this->imres['bg']); $x++ )
				for ($y = 0; $y < imagesy($this->image) / imagesy($this->imres['bg']); $y++ )
					imagecopy($this->image, $this->imres['bg'], $x * imagesx($this->imres['bg']), $y * imagesy($this->imres['bg']), 0, 0, imagesx($this->imres['bg']), imagesy($this->imres['bg']));

		
		// draw location circles
		if ($this->drawlocations) 
			foreach ($this->Locations as $loc)
			{
				if (!$loc->enabled)
					continue;
			
				imagefilledellipse($this->image, 
					$loc->x * $this->brick_w, // x
					$loc->y * $this->brick_h, // y
					500, 500, // radius
					imagecolorallocatealpha($this->image, 200, 200, 200, 99) );
				
				// TODO: draw $loc->text
			}
		
		
		// draw bricks
		for ($x = 0; $x < $this->Header->MapSizeX; $x++)
			for ($y = 0; $y < $this->Header->MapSizeY; $y++)
			{
				// pass empty bricks
				if (!isset($this->Bricks[$x][$y]) || $this->Bricks[$x][$y] == 0)
					continue;
			
				if (!$this->drawspecialobjects)
					if ($this->Bricks[$x][$y] >= 34 && $this->Bricks[$x][$y] <= 37) // respawns and empty
						continue;
			
				// if brick with this index has better image
				if ( $this->replacefineimages && $this->replaceBrickFineImage($this->Bricks[$x][$y], $x, $y) )
					continue;
				
					$brick = $this->getBrickImageByIndex($this->Bricks[$x][$y]);
				
				// transparent for water(31) and lava(32)
				$transparency = ($this->Bricks[$x][$y] == 31 || $this->Bricks[$x][$y] == 32) ? 75 : 100;
				
				imagecopymerge($this->image, 
					$brick, $x * $this->brick_w, 
					$y * $this->brick_h, 
					0, 
					0,
					$this->brick_w, $this->brick_h, $transparency);
			}

		
		// enable antialiasing (smooth teleport lines)
		#imageantialias($this->image, true);
		
		
		// sort objects by objtype descending
		//  it's needed to display button-to-door arrow on front layer,
		//  cause button objtype=2 and door objtype=3
		usort($this->Objects, function($a, $b) {
			return $a->objtype < $b->objtype;
		});
		
		// draw special objects
		foreach ($this->Objects as $obj)
		{
			if (!$obj->active)
				continue;
				
			switch ($obj->objtype)
			{
				// teleport
				case 1:
					imagecopy($this->image, $this->imres['portal'], 
						$obj->x * $this->brick_w - $this->brick_w / 2, 
						$obj->y * $this->brick_h - $this->brick_h * 2, 
						0, 
						0, 
						imagesx($this->imres['portal']), imagesy($this->imres['portal']) );

					// draw arrow to goto position
					if ($this->drawspecialobjects)
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
					
					imagecopy($this->image, $this->imres['button'], 
								$obj->x * $this->brick_w + ($this->brick_w - $button_size) / 2,
								$obj->y * $this->brick_h + ($this->brick_h - $button_size) / 2,
								$obj->orient * $button_size, // offset for button palette
								0,
								$button_size,
								$button_size);
					
					// draw arrows matched to the doors
					if ($this->drawspecialobjects)
						foreach ($this->Objects as $door_obj)
							if ($door_obj->objtype == 3 && $door_obj->targetname == $obj->target)
								arrow($this->image, 
									$obj->x * $this->brick_w + $this->brick_w / 2, // button center x
									$obj->y * $this->brick_h + $this->brick_h / 2, // button center y
									$door_obj->x * $this->brick_w + (($door_obj->orient == 1) ? $this->brick_w / 2 : $this->brick_w * $door_obj->length / 2), // door center x
									$door_obj->y * $this->brick_h + (($door_obj->orient == 0) ? $this->brick_h / 2 : $this->brick_h * $door_obj->length / 2), // door center y
									5, 1,
									imagecolorallocatealpha($this->image, 182, 255, 0, 50) );
						
					break;
					
				// door
				case 3:
					// clone door image to vertical(0) or horizontal(1) depending $ibj->orient
					for ($i = 0; $i < $obj->length; $i++)
						imagecopy($this->image, $this->imres['door'], 
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
					if ($this->drawspecialobjects)
						imagefilledrectangle($this->image, 
							$obj->x * $this->brick_w, 
							$obj->y * $this->brick_h, 
							$obj->x * $this->brick_w + $obj->length * $this->brick_w, 
							$obj->y * $this->brick_h + $obj->dir * $this->brick_h, 
							imagecolorallocatealpha($this->image, 76, 255, 0, 99));
					
					break;
			}
		}
		
		return $this;
	}
	
	
	// return brick image object by it's index in palette
	function getBrickImageByIndex($index)
	{
		$pal = $this->imres['palette'];

		if ($index >= 54 && $index <= 181)
		{
			if ( isset($this->imres['custom_palette']) )
			{
				$pal = $this->imres['custom_palette'];
				$index -= 54;
			}
			else
				$index -= 6;
		}
	
		// default palette width: 8 bricks
		// custom palette width: 7 bricks
		$pal_width = imagesx($pal) / $this->brick_w;
		
		// find brick position
		$x = $index % $pal_width * $this->brick_w;
		$y = floor($index / $pal_width ) * $this->brick_h;
		
		$brick = imagecreatetruecolor($this->brick_w, $this->brick_h); 
		
		// copy brick from the palette
		imagecopy($brick, $pal, 0, 0, $x, $y, $this->brick_w, $this->brick_h);
		
		// get transparent color of palette
		$color = imagecolortransparent($pal);
		
		if ($this->debug)
			echo '<br>color: ' . $color;

		
		// set brick transparent color
		imagecolortransparent($brick, $color);
		
		
		#if ($this->debug) // save each brick as single image
		#imagepng($brick, "bricks\\$index.png");
		
		return $brick;
	}
	
	// draw better image if exists
	function replaceBrickFineImage($index, $x, $y)
	{
		switch($index)
		{
			// yellow armor
			case 17:
				imagecopy($this->image, $this->imres['armor'], 
					$x * $this->brick_w, 
					$y * $this->brick_h, 
					0, 0, 
					$this->brick_w, $this->brick_h);
				break;
				
			// red armor
			case 18:
				imagecopy($this->image, $this->imres['armor'], 
					$x * $this->brick_w, 
					$y * $this->brick_h, 
					20 * $this->brick_w, 0, 
					$this->brick_w, $this->brick_h);
				break;
				
			// megahealth
			case 22:
				$size = 24;
				imagecopy($this->image, $this->imres['fine_mega'], 
					$x * $this->brick_w + ($this->brick_w - $size - 1) / 2, 
					$y * $this->brick_h - ($size - $this->brick_h - 1), 
					0, 0, 
					$size, $size);
				break;
				
			// regeneration
			case 23:
				$this->_drawFineItem('fine_regen', $x, $y);
				break;
				
			// battlesuite
			case 24:
				$this->_drawFineItem('fine_battle', $x, $y);
				break;
				
			// haste
			case 25:
				$this->_drawFineItem('fine_haste', $x, $y);
				break;
				
			// quaddamage
			case 26:
				$this->_drawFineItem('fine_quad', $x, $y);
				break;
				
			// flight
			case 27:
				$this->_drawFineItem('fine_flight', $x, $y);
				break;
				
			// invisibility
			case 28:
				$this->_drawFineItem('fine_invis', $x, $y);
				break;

			// blue flag
			case 40:
				$size_x = 36;
				$size_y = 41;
				imagecopy($this->image, $this->imres['flag'], 
					$x * $this->brick_w + ($this->brick_w - $size_x) / 2, 
					$y * $this->brick_h - ($size_y - $this->brick_h), 
					0, 0, 
					$size_x, $size_y);
				break;
				
			// red flag
			case 41:
				$size_x = 36;
				$size_y = 41;
				imagecopy($this->image, $this->imres['flag'], 
					$x * $this->brick_w + ($this->brick_w - $size_x) / 2, 
					$y * $this->brick_h - ($size_y - $this->brick_h), 
					$size_x * 14, 0, 
					$size_x, $size_y);
				break;
				
					
			// death place fill with opacity red color
			case 33:
				imagefilledrectangle($this->image, 
					$x * $this->brick_w, 
					$y * $this->brick_h, 
					$x * $this->brick_w + $this->brick_w - 1, 
					$y * $this->brick_h + $this->brick_h, 
					imagecolorallocatealpha($this->image, 255, 0, 0, 99));
				break;
							
			default: 
				return false;
		}
		return true;
	}
	function _drawFineItem($res, $x, $y)
	{
		imagecopy($this->image, $this->imres[$res], $x * $this->brick_w, 
					$y * $this->brick_h - $this->brick_h, 0, 0, 37, 32);
	}
	
	
	
	
	// preload image resources
	function loadResources()
	{
		$this->imres['palette'] = imagecreatefrompng('data/palette.png');
		// set palette transparent color
		$color = imagecolorat($this->imres['palette'], 0, 0); // get first pixel color
		imagecolortransparent($this->imres['palette'], $color);
		
		if ($this->background)
			if ( !file_exists('data/' . $this->background) )
				throw new Exception('Background file data/' . $this->background . ' doesn\'t not exist!');
			else
				$this->imres['bg'] = imagecreatefromjpeg('data/' . $this->background);
		
		$this->imres['portal'] = imagecreatefrompng('data/portal.png');
		$this->imres['door'] = imagecreatefrompng('data/door.png');
		$this->imres['button'] = imagecreatefrompng('data/button.png');
		
		if ($this->replacefineimages)
		{
			$this->imres['armor'] = imagecreatefrompng('data/armor.png');
			$this->imres['flag'] = imagecreatefrompng('data/flag.png');
			$this->imres['fine_battle'] = imagecreatefrompng('data/fine_battle.png');
			$this->imres['fine_fly'] = imagecreatefrompng('data/fine_fly.png');
			$this->imres['fine_haste'] = imagecreatefrompng('data/fine_haste.png');
			$this->imres['fine_invis'] = imagecreatefrompng('data/fine_invis.png');
			$this->imres['fine_mega'] = imagecreatefrompng('data/fine_mega.png');
			$this->imres['fine_quad'] = imagecreatefrompng('data/fine_quad.png');
			$this->imres['fine_regen'] = imagecreatefrompng('data/fine_regen.png');
			$this->imres['fine_regen'] = imagecreatefrompng('data/fine_regen.png');
		}
	}
	
	

	function getInt()
	{
		$data = $this->read(4);

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

		// convert to word with reading big endian
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
	
	
	function putInt($value)
	{
		// convert to int with writing little endian
		$value = pack("V*", $value);
					
		$this->write($value);
	}
	
	function putWord($value, $long = false)
	{
		// convert to word with writing little endian
		$value = !$long
					? pack("s*", $value)
					: pack("S*", $value);
					
		$this->write($value);
	}
	function putString($value, $fix_len = false)
	{
		// fill string to fixed length
		if ($fix_len)
			$value = str_pad($value, $fix_len, "phpmapeditor", STR_PAD_RIGHT);
		
		$this->write($value);
	}
	function putChar($value)
	{
		$this->write($value);
	}
	function putByte($value)
	{
		$this->putChar( chr($value) );
	}
	function putBool($value)
	{
		$this->putByte($value ? '1' : '0');
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
	
	// write bytes to stream
	function write($data)
	{
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
	function getFileName($filename = false)
	{
		if (!$filename)
			$filename = basename($this->filename);
			
		return preg_replace("/\\.[^.\\s]{3,4}$/", "", $filename);
	}
	

	function __destruct()
	{
		// free resources
		if ($this->handle)
			fclose($this->handle);

		if ($this->image)
			imagedestroy($this->image);
			
		// free resources
		if ($this->imres)
			foreach ($this->imres as $res)
				@imagedestroy($this->imres);
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
	public $active = 0; // boolean
	public $x = 0, $y = 0, $length = 0, $dir = 0, $wait = 0; // word
	public $targetname = 0, $target = 0, $orient = 0, $nowanim = 0, $special = 0; // word
	public $objtype = 0; // byte
}

// palette
class TMapEntry
{
	public $EntryType; // byte header + string[3]
	public $DataSize = 0; // longint
	public $Reserved1 = 0; // byte
	public $Reserved2 = 0; // word
	public $Reserved3 = 0; // integer
	public $Reserved4 = 0; // longint
	public $Reserved5 = 0; // cardinal
	public $Reserved6 = false; // boolean
}

// location
class TLocationText
{
	public $enabled = 0; // boolean
	public $x = 0, $y = 0; // byte
	public $text = ''; // string[64]
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
			$data = '';
			foreach ($header AS $h)
			{
				$data .= $h;
			}
			
			//save pixels
			for ($y=$hei-1; $y>=0; $y--)
			{
				for ($x=0; $x<$wid; $x++)
				{
					$rgb = imagecolorat($img, $x, $y);
					$data .= self::byte3($rgb);
				}
				$data .= $wid_pad;
			}
			return $data;
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

	// http://www.xbdev.net/image_formats/bmp/index.php
	// http://www.fileformat.info/format/bmp/egff.htm
	public static function imagecreatefrombmpstream($stream) 
	{
		$temp = unpack("H*", $stream);
		$hex = $temp[1];

		$header = substr($hex, 0, 54*2);
		if (substr($header, 0, 4) == "424d") // BM
		{
			$header_parts = str_split($header, 2);
			$hsize = hexdec($header_parts[0xF] . $header_parts[0xE]); // header info size
			
			$width = hexdec($header_parts[0x13] . $header_parts[0x12]);
			
			// BMP v2, header info size = 12 bytes
			if ($hsize == 12)
			{
				$height = hexdec($header_parts[0x15] . $header_parts[0x14]);
				$bitcount = hexdec($header_parts[0x18]); // bits per pixel 8/16/24/32
				$offset = hexdec($header_parts[0xB] . $header_parts[0xA]);
			}
			// normal bmp with header size = 54 bytes (+ may be garbage bytes before header and image data)
			else
			{
				$height = hexdec($header_parts[0x17] . $header_parts[0x16]);
				$bitcount = hexdec($header_parts[0x1C]); // bits per pixel 8/16/24/32
				$imagesize = hexdec($header_parts[0x25] . $header_parts[0x24] . $header_parts[0x23] . $header_parts[0x22]); // size of image content without header
				$offset = strlen($stream) - $imagesize;
			}
			
			#debug
			#echo "<br>hsize:$hsize |w:$width |h:$height |bits:$bitcount |offset:$offset" ;

			unset($header_parts);
		}
		$x = 0;
		$y = 1;
		$img = imagecreatetruecolor($width, $height);
		$body = substr($hex, $offset * 2); // set offset
		$body_size = (strlen($body) / 2);
		$header_size = ($width * $height);
		$usePadding = ($body_size > ($header_size * $bitcount/8) + 4);
		for ($i = 0; $i < $body_size; $i+=$bitcount/8)
		{
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


// -- GD FUNCTIONS --

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

// return hanged
function resizeImage($src, $max_size = 200, $text=false)
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
		
		$txt_color=$background = imagecolorallocate($im, 255, 255, 255);
		$txt_file="data/arial.ttf";
		$txt_fontsize=10.5;

		imagettftext ($im, $txt_fontsize, 0,  10, $tn_height-6, $txt_color, $txt_file, $text);
	}

	return $im;
}

// return prop small size from the source size
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

// example: ff0000 -> ff
function inverseHex( $color )
{
	$newcolor = str_split($color, 2);
	$newcolor = array_reverse($newcolor);

	return implode($newcolor);
}





