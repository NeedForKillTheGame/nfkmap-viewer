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
	//  value is an index of the image file "data/bg_[index].jpg"
	//  if value === false, then fill background with black color
	//  if value === null then use background index from the MAP Header->BG
	public $background = null;
	
	// replace some item images to better quality (armor, quad, etc.)
	public $replacefineimages = true;
	
	// draw location circles (not good view)
	public $drawlocations = false;
	
	// draw objects like door triggers, arrows, respawns and empty bricks
	public $drawspecialobjects = true;	

	
	// debug flag will display a lot of boring info
	public $debug = false;
	
	
	/* --- SETUP END --- */
	
	
	
	
	
	// header (map info)
	public $Header;
	// bricks
	public $Bricks = array();
	// special objects
	public $Objects = array();
	// map locations
	public $Locations = array();

	
	private $filename = 'new.mapa';
	
	// file handle
	private $handle; 
	// current position
	private $pos = 0;

	// resources
	private $imres;
	
	// palette transparent color
	private $transparent_color = false;
	
	// palette original image
	private $custom_palette_bin = false;
	
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
			$this->DrawMap();

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
			$this->DrawMap();
	
		header('Content-Type: image/png; filename="' . $this->Header->MapName . '"');
		
		imagepng($this->image);
		#return ob_get_contents(); // return bytes
	}	
	
	// save map bytes to a mapa file
	public function SaveMap($filename = false)
	{
		if (!$filename)
			$filename = $this->getFileName();
	

		// write generated data to a file
		file_put_contents($filename . '.mapa', $this->GetMapString() );
	}
	
	// return map binary string
	public function GetMapString()
	{
		$this->stream = '';

		// write header
			$this->Header->ID = 'NMAP';
		$this->putString($this->Header->ID);
			$this->Header->Version = 3;
		$this->putByte($this->Header->Version);
		
		$this->putByte( strlen($this->Header->MapName) ); // size of next readable string
		$this->putString($this->Header->MapName, 70);
		$this->putByte( strlen($this->Header->Author) ); // size of next readable string
		$this->putString($this->Header->Author, 70);
		
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
		if ( $this->imres && isset($this->imres['custom_palette']) && $this->imres['custom_palette'] )
		{
			$pal_bz = bzcompress($this->custom_palette_bin);
			
			// entry
			$this->putByte(0x03); // 0x03 header of string
			$this->putString('pal'); 
			
			$this->putInt(strlen($pal_bz)); // bmp size
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
				
				$this->putInt(count($this->Locations) * $this->tlocation_size); // locations size
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
					$this->putByte( strlen($loc->text) ); // size of next readable string
					$this->putString($loc->text, 64);
				}
		}
		
		return $this->stream;
	}
	
	// open and parse map file (or demo file)
	public function LoadMap()
	{
		if (!$this->handle = @fopen($this->filename, 'r'))
			throw new Exception("Can't open file " . $this->filename);

		// if file is a demo
		if ( $this->getString(7) == "NFKDEMO")
		{
			$this->getByte(); // unknown byte 0x2D (ignore)
			
			// read bz compressed data
			$data_bz = '';
			while (!feof($this->handle))
				$data_bz .= fread($this->handle, 8192);
			fclose($this->handle);
			
			// decompress data
			$data_bin = bzdecompress($data_bz);
			
			// create inmemory stream and write data
			$this->handle = fopen("php://memory", 'r+');
			fputs($this->handle, $data_bin);

			// set position to start
			rewind($this->handle);
			
			
			#if ($this->debug)
			#	file_put_contents('test.ndm', stream_get_contents($this->handle));
		}
		$this->pos = 0;
		$this->stream = '';
		
		// read header
		$this->Header = new THeader();
		
		$this->Header->ID = $this->getString(4);
		if ($this->Header->ID != "NMAP" && $this->Header->ID != "NDEM")
			throw new Exception($this->filename . " is not NFK map/demo");

		$this->Header->Version = $this->getByte();
		if ($this->Header->Version < 3 || $this->Header->Version > 7)
			throw new Exception("Incorrect map version");
		
		
		$b = $this->getByte(); // size of next readable string
		$this->Header->MapName = substr($this->getString(70), 0, $b);
		$b = $this->getByte(); // size of next readable string
		$this->Header->Author = substr($this->getString(70), 0, $b);
		
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
				
				// decompress if map and nothing to do if demo file
				$this->custom_palette_bin = ($this->Header->ID == "NDEM")
								? $pal_bz
								: bzdecompress($pal_bz);
				
				// create gd object of palette
				if ( !$this->imres['custom_palette'] = imagecreatefrombmp($this->custom_palette_bin) )
					continue;

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
					file_put_contents("palette_map.bmp", $this->custom_palette_bin); // original
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
					$b = $this->getByte(); // size of next readable string
					$loc->text = substr($this->getString(64), 0, $b);
					
					$this->Locations[$i] = $loc;
				}
			}
			else // end of file
			{
				if ($this->debug)
					echo "<br>end of file: " . strlen($this->stream) . '|' . $this->pos;
					
				// FIXME: not needed cause stream is different with original
				// remove TMapEntry length from the end of stream if file bigger than it should
				#if ( strlen($this->stream) == $this->pos )
				#	$this->stream = substr($this->stream, 0, strlen($this->stream) - 24);
				// fix header id and version in stream for map extracted from demo
				#if ($this->Header->ID == "NDEM")
				#	$this->stream = 'NMAP' . chr(3) . substr($this->stream, 5, strlen($this->stream) - 5);
		
				break;
			}
		}
		
		if ($this->debug)
			print_r($this);
			
		return $this;
	}

	
	// draw map to $this->image
	public function DrawMap()
	{
		// load image resources
		$this->loadResources();
	
		$width = $this->Header->MapSizeX * $this->brick_w;
		$height = $this->Header->MapSizeY * $this->brick_h;


		// create map layer
		$this->image = imagecreatetruecolor($width, $height);

		
		// fill image with repeated background
		if ( isset($this->imres['bg']) )
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
									$door_obj->x * $this->brick_w + (($door_obj->orient == 1 || $obj->orient == 3) ? $this->brick_w / 2 : $this->brick_w * $door_obj->length / 2), // door center x
									$door_obj->y * $this->brick_h + (($door_obj->orient == 0 || $obj->orient == 2) ? $this->brick_h / 2 : $this->brick_h * $door_obj->length / 2), // door center y
									5, 1,
									imagecolorallocatealpha($this->image, 182, 255, 0, 50) );
						
					break;
					
				// door
				case 3:
					// clone door image to vertical(0) or horizontal(1) depending $ibj->orient
					for ($i = 0; $i < $obj->length; $i++)
						imagecopy($this->image, $this->imres['door'], 
							$obj->x * $this->brick_w + (($obj->orient == 1 || $obj->orient == 3) ? 0 : $this->brick_w * $i),
							$obj->y * $this->brick_h + (($obj->orient == 0 || $obj->orient == 2) ? 0 : $this->brick_h * $i),
							($obj->orient == 1 || $obj->orient == 3) ? 0 : $this->brick_w,
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
	private function getBrickImageByIndex($index)
	{
		$pal = $this->imres['palette'];

		if ($index >= 54 && $index <= 181)
		{
			if ( isset($this->imres['custom_palette']) && $this->imres['custom_palette'] )
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
		
		#if ($this->debug)
		#	echo '<br>color: ' . $color;

		
		// set brick transparent color
		imagecolortransparent($brick, $color);
		
		
		#if ($this->debug) // save each brick as single image
		#imagepng($brick, "bricks\\$index.png");
		
		return $brick;
	}
	
	// draw better image if exists
	private function replaceBrickFineImage($index, $x, $y)
	{
		switch($index)
		{
			// yellow armor
			case 17:
				imagecopy($this->image, $this->imres['fine_armor'], 
					$x * $this->brick_w, 
					$y * $this->brick_h, 
					0, 0, 
					$this->brick_w, $this->brick_h);
				break;
				
			// red armor
			case 18:
				imagecopy($this->image, $this->imres['fine_armor'], 
					$x * $this->brick_w, 
					$y * $this->brick_h, 
					$this->brick_w, 0, 
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
				$this->_drawFineItem('fine_power', $x, $y, 2);
				break;
				
			// battlesuite
			case 24:
				$this->_drawFineItem('fine_power', $x, $y, 3);
				break;
				
			// haste
			case 25:
				$this->_drawFineItem('fine_power', $x, $y, 0);
				break;
				
			// quaddamage
			case 26:
				$this->_drawFineItem('fine_power', $x, $y, 4);
				break;
				
			// flight
			case 27:
				$this->_drawFineItem('fine_power', $x, $y, 5);
				break;
				
			// invisibility
			case 28:
				$this->_drawFineItem('fine_power', $x, $y, 1);
				break;

			// blue flag
			case 40:
				$size_x = 36;
				$size_y = 41;
				imagecopy($this->image, $this->imres['fine_flag'], 
					$x * $this->brick_w + ($this->brick_w - $size_x) / 2, 
					$y * $this->brick_h - ($size_y - $this->brick_h), 
					0, 0, 
					$size_x, $size_y);
				break;
				
			// red flag
			case 41:
				$size_x = 36;
				$size_y = 41;
				imagecopy($this->image, $this->imres['fine_flag'], 
					$x * $this->brick_w + ($this->brick_w - $size_x) / 2, 
					$y * $this->brick_h - ($size_y - $this->brick_h), 
					$size_x, 0, 
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
	private function _drawFineItem($res, $x, $y, $index)
	{
		imagecopy($this->image, $this->imres[$res], $x * $this->brick_w, 
					$y * $this->brick_h - $this->brick_h, $index * 37, 0, 37, 32);
	}
	
	
	
	
	// preload image resources
	private function loadResources()
	{
		$this->imres['palette'] = imagecreatefrompng('data/palette.png');
		// set palette transparent color
		$color = imagecolorat($this->imres['palette'], 0, 0); // get first pixel color
		imagecolortransparent($this->imres['palette'], $color);

		if ($this->background !== false)
		{
			// if background index was not set then use MAP background
			$bg_filename = 'data/bg_' . (($this->background !== null) ? $this->background : $this->Header->BG) . '.jpg';

			if ( file_exists( $bg_filename ) )
				$this->imres['bg'] = imagecreatefromjpeg($bg_filename);
		}
	
		$this->imres['portal'] = imagecreatefrompng('data/portal.png');
		$this->imres['door'] = imagecreatefrompng('data/door.png');
		$this->imres['button'] = imagecreatefrompng('data/button.png');
		
		if ($this->replacefineimages)
		{
			$this->imres['fine_armor'] = imagecreatefrompng('data/fine_armor.png');
			$this->imres['fine_flag'] = imagecreatefrompng('data/fine_flag.png');
			$this->imres['fine_power'] = imagecreatefrompng('data/fine_power.png');
			$this->imres['fine_mega'] = imagecreatefrompng('data/fine_mega.png');
		}
	}
	
	

	private function getInt()
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
	private function getWord($long = false)
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
	
	private function getString($len)
	{
		return $this->read($len);
	}
	private function getChar()
	{
		return $this->read(1);
	}
	private function getByte()
	{
		return ord( $this->getChar() );
	}
	private function getBool()
	{
		return $this->getByte() === 1 ? true : false;
	}
	
	
	private function putInt($value)
	{
		// convert to int with writing little endian
		$value = pack("V*", $value);
					
		$this->write($value);
	}
	
	private function putWord($value, $long = false)
	{
		// convert to word with writing little endian
		$value = !$long
					? pack("s*", $value)
					: pack("S*", $value);
					
		$this->write($value);
	}
	private function putString($value, $fix_len = false)
	{
		// fill string to fixed length
		if ($fix_len)
			$value = str_pad($value, $fix_len, chr(0), STR_PAD_RIGHT);
		
		$this->write($value);
	}
	private function putChar($value)
	{
		$this->write($value);
	}
	private function putByte($value)
	{
		$this->putChar( chr($value) );
	}
	private function putBool($value)
	{
		$this->putByte($value ? '1' : '0');
	}
	
	
	// read bytes from current position to specified length
	private function read($length)
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
	private function write($data)
	{
		// add readed bytes to stream
		$this->stream .= $data;
		
		return $data;
	}
		
	// return map name from file name (without extension)
	private function getFileName($filename = false)
	{
		if (!$filename)
			$filename = basename($this->filename);
			
		return preg_replace("/\\.[^.\\s]{3,4}$/", "", $filename);
	}
	

	public function __destruct()
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







// -- GD FUNCTIONS --

function hexcoloralloc($im, $hex)
{ 
  $a = hexdec(substr($hex,0,2)); 
  $b = hexdec(substr($hex,2,2)); 
  $c = hexdec(substr($hex,4,2)); 

  return imagecolorallocate($im, $a, $b, $c); 
} 

// draw arrow
function arrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color)
{
    $distance = sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));

	if ($distance == 0)
		return;
	
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
		
		$txt_color = imagecolorallocate($im, 255, 255, 255);
		$txt_file = "data/arial.ttf";
		$txt_fontsize = 10.5;

		imagettftext ($im, $txt_fontsize, 0,  10, $tn_height-6, $txt_color, $txt_file, $text);
	}

	return $im;
}

// return prop small size from the source size
function getpropsize($width, $height, $max)
{
	if ($width <= $max and $height <= $max)
		return array($width, $height);
		
	$lager = ($width > $height) ? $width : $height; //  ñòîðîíà, êîòîðàÿ äëèííåå
	
	$k = $lager / $max; // âî ñêîëüêî ðàç óìåíüøèòü

	$w = @round($width / $k); // 1%
	$h = @round($height / $k); // 1%

	return array($w, $h);
}

// example: ff0000 -> 0000ff
function inverseHex( $hex )
{
	$newhex = array_reverse( str_split($hex, 2) );
	return implode($newhex);
}








// -- BMP SUPPORT --
function imagecreatefrombmp($filename_or_stream_or_binary){
	return GdBmp::load($filename_or_stream_or_binary);
}



/**
 * Copyright (c) 2011, oov. All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * 
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *  - Neither the name of the oov nor the names of its contributors may be used to
 *    endorse or promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA,
 * OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 * 
 * bmp ãã¡ã¤ã«ã GD ã§ä½¿ããããã«
 * 
 * ä½¿ç¨ä¾:
 *   //ãã¡ã¤ã«ããèª­ã¿è¾¼ãå ´åã¯GDã§PNGãªã©ãèª­ã¿è¾¼ãã®ã¨åããããªæ¹æ³ã§å¯
 *   $image = imagecreatefrombmp("test.bmp");
 *   imagedestroy($image);
 * 
 *   //æå­åããèª­ã¿è¾¼ãå ´åã¯ä»¥ä¸ã®æ¹æ³ã§å¯
 *   $image = GdBmp::loadFromString(file_get_contents("test.bmp"));
 *   //èªåå¤å®ãããã®ã§ç ´æãã¡ã¤ã«ã§ãªããã°ããã§ãä¸æããã
 *   //$image = imagecreatefrombmp(file_get_contents("test.bmp"));
 *   imagedestroy($image);
 * 
 *   //ãã®ä»ä»»æã®ã¹ããªã¼ã ããã®èª­ã¿è¾¼ã¿ãå¯è½
 *   $stream = fopen("http://127.0.0.1/test.bmp");
 *   $image = GdBmp::loadFromStream($stream);
 *   //èªåå¤å®ãããã®ã§ããã§ããã
 *   //$image = imagecreatefrombmp($stream);
 *   fclose($stream);
 *   imagedestroy($image);
 * 
 * å¯¾å¿ãã©ã¼ããã
 *   1bit
 *   4bit
 *   4bitRLE
 *   8bit
 *   8bitRLE
 *   16bit(ä»»æã®ããããã£ã¼ã«ã)
 *   24bit
 *   32bit(ä»»æã®ããããã£ã¼ã«ã)
 *   BITMAPINFOHEADER ã® biCompression ã BI_PNG / BI_JPEG ã®ç»å
 *   ãã¹ã¦ã®å½¢å¼ã§ããããã¦ã³/ããã ã¢ããã®ä¸¡æ¹ããµãã¼ã
 *   ç¹æ®ãªããããã£ã¼ã«ãã§ãããããã£ã¼ã«ããã¼ã¿ãæ­£å¸¸ãªãèª­ã¿è¾¼ã¿å¯è½
 *
 * ä»¥ä¸ã®ãã®ã¯éå¯¾å¿
 *   BITMAPV4HEADER ã¨ BITMAPV5HEADER ã«å«ã¾ããè²ç©ºéã«é¢ããæ§ããªæ©è½
 **/
// https://bitbucket.org/oov/php-bmp/raw/09808861a72ac1619638ed376a0bbffe149ff0cc/GdBmp.php

class GdBmp{
	public static function load($filename_or_stream_or_binary){
		if (is_resource($filename_or_stream_or_binary)){
			return self::loadFromStream($filename_or_stream_or_binary);
		} else if (is_string($filename_or_stream_or_binary) && strlen($filename_or_stream_or_binary) >= 26){
			$bfh = unpack("vtype/Vsize", $filename_or_stream_or_binary);
			if ($bfh["type"] == 0x4d42){
				return self::loadFromString($filename_or_stream_or_binary);
			}
		}
		return self::loadFromFile($filename_or_stream_or_binary);
	}
	public static function loadFromFile($filename){
		$fp = fopen($filename, "rb");
		if ($fp === false){
			return false;
		}

		$bmp = self::loadFromStream($fp);

		fclose($fp);
		return $bmp;
	}

	public static function loadFromString($str){
		//data scheme ããå¤ããã¼ã¸ã§ã³ããå¯¾å¿ãã¦ãããããªã®ã§ php://memory ãä½¿ã
		$fp = fopen("php://memory", "r+b");
		if ($fp === false){
			return false;
		}

		if (fwrite($fp, $str) != strlen($str)){
			fclose($fp);
			return false;
		}

		if (fseek($fp, 0) === -1){
			fclose($fp);
			return false;
		}

		$bmp = self::loadFromStream($fp);

		fclose($fp);
		return $bmp;
	}

	public static function loadFromStream($stream){
		$buf = fread($stream, 14); //2+4+2+2+4
		if ($buf === false){
			return false;
		}

		//ã·ã°ããã£ãã§ãã¯
		if ($buf[0] != 'B' || $buf[1] != 'M'){
			return false;
		}

		$bitmap_file_header = unpack(
			//BITMAPFILEHEADERæ§é ä½
			"vtype/".
			"Vsize/".
			"vreserved1/".
			"vreserved2/".
			"Voffbits", $buf
		);
		
		return self::loadFromStreamAndFileHeader($stream, $bitmap_file_header);
	}

	public static function loadFromStreamAndFileHeader($stream, array $bitmap_file_header){
		if ($bitmap_file_header["type"] != 0x4d42){
			return false;
		}

		//æå ±ããããµã¤ãºãåã«å½¢å¼ãåºå¥ãã¦èª­ã¿è¾¼ã¿
		$buf = fread($stream, 4);
		if ($buf === false){
			return false;
		}
		list(,$header_size) = unpack("V", $buf);


		if ($header_size == 12){
			$buf = fread($stream, $header_size - 4);
			if ($buf === false){
				return false;
			}

			extract(unpack(
				//BITMAPCOREHEADERæ§é ä½ - OS/2 Bitmap
				"vwidth/".
				"vheight/".
				"vplanes/".
				"vbit_count", $buf
			));
			//é£ãã§ããªãåã¯ 0 ã§åæåãã¦ãã
			$clr_used = $clr_important = $alpha_mask = $compression = 0;

			//ãã¹ã¯é¡ã¯åæåãããªãã®ã§ããã§å²ãå½ã¦ã¦ãã
			$red_mask   = 0x00ff0000;
			$green_mask = 0x0000ff00;
			$blue_mask  = 0x000000ff;
		} else if (124 < $header_size || $header_size < 40) {
			//æªç¥ã®å½¢å¼
			return false;
		} else {
			//ãã®æç¹ã§36ãã¤ãèª­ãããã¨ã¾ã§ã¯ããã£ã¦ãã
			$buf = fread($stream, 36); //æ¢ã«èª­ãã é¨åã¯é¤å¤ãã¤ã¤BITMAPINFOHEADERã®ãµã¤ãºã ãèª­ã
			if ($buf === false){
				return false;
			}

			//BITMAPINFOHEADERæ§é ä½ - Windows Bitmap
			extract(unpack(
				"Vwidth/".
				"Vheight/".
				"vplanes/".
				"vbit_count/".
				"Vcompression/".
				"Vsize_image/".
				"Vx_pels_per_meter/".
				"Vy_pels_per_meter/".
				"Vclr_used/".
				"Vclr_important", $buf
			));
			
			// HarpyWar: fix stream size if wrong
			$pos = ftell($stream);
			rewind($stream);
			$bitmap_file_header["size"] = strlen( stream_get_contents($stream) );
			fseek($stream, $pos);
			
			//è² ã®æ´æ°ãåãåãå¯è½æ§ããããã®ã¯èªåã§å¤æãã
			if ($width  & 0x80000000){ $width  = -(~$width  & 0xffffffff) - 1; }
			if ($height & 0x80000000){ $height = -(~$height & 0xffffffff) - 1; }
			if ($x_pels_per_meter & 0x80000000){ $x_pels_per_meter = -(~$x_pels_per_meter & 0xffffffff) - 1; }
			if ($y_pels_per_meter & 0x80000000){ $y_pels_per_meter = -(~$y_pels_per_meter & 0xffffffff) - 1; }

			//ãã¡ã¤ã«ã«ãã£ã¦ã¯ BITMAPINFOHEADER ã®ãµã¤ãºãããããï¼æ¸ãè¾¼ã¿ééãï¼ï¼ã±ã¼ã¹ããã
			//èªåã§ãã¡ã¤ã«ãµã¤ãºãåã«éç®ãããã¨ã§åé¿ã§ãããã¨ãããã®ã§åè¨ç®ã§ããããªãæ­£å½æ§ãèª¿ã¹ã
			//ã·ã¼ã¯ã§ããªãã¹ããªã¼ã ã®å ´åå¨ä½ã®ãã¡ã¤ã«ãµã¤ãºã¯åå¾ã§ããªãã®ã§ã$bitmap_file_headerã«ãµã¤ãºç³åããªããã°ãããªã
			if ($bitmap_file_header["size"] != 0){
				$colorsize = $bit_count == 1 || $bit_count == 4 || $bit_count == 8 ? ($clr_used ? $clr_used : pow(2, $bit_count))<<2 : 0;
				$bodysize = $size_image ? $size_image : ((($width * $bit_count + 31) >> 3) & ~3) * abs($height);
				$calcsize = $bitmap_file_header["size"] - $bodysize - $colorsize - 14;
				//æ¬æ¥ã§ããã°ä¸è´ããã¯ããªã®ã«åããªãæã¯ãå¤ããããããªããããªãï¼BITMAPV5HEADERã®ç¯å²åãªãï¼è¨ç®ãã¦æ±ããå¤ãæ¡ç¨ãã
				if ($header_size < $calcsize && 40 <= $header_size && $header_size <= 124){
					$header_size = $calcsize;
				}
				
				// HarpyWar: fix offset if wrong
				if ( $bitmap_file_header["offbits"] != ($bitmap_file_header["size"] - $bodysize) )
				{
					fseek($stream, $bitmap_file_header["size"] - $bodysize);
					
					// set header size to pass next condition
					$header_size = 40;
				}
			}
			
			//BITMAPV4HEADER ã BITMAPV5HEADER ã®å ´åã¾ã èª­ãã¹ããã¼ã¿ãæ®ã£ã¦ããå¯è½æ§ããã
			if ($header_size - 40 > 0){
				$buf = fread($stream, $header_size - 40);
				if ($buf === false){
					return false;
				}

				extract(unpack(
					//BITMAPV4HEADERæ§é ä½(Windows95ä»¥é)
					//BITMAPV5HEADERæ§é ä½(Windows98/2000ä»¥é)
					"Vred_mask/".
					"Vgreen_mask/".
					"Vblue_mask/".
					"Valpha_mask", $buf . str_repeat("\x00", 120)
				));
			} else {
				$alpha_mask = $red_mask = $green_mask = $blue_mask = 0;
			}

			//ãã¬ããããªããã«ã©ã¼ãã¹ã¯ããªãæ
			if (
				($bit_count == 16 || $bit_count == 24 || $bit_count == 32)&&
				$compression == 0 &&
				$red_mask == 0 && $green_mask == 0 && $blue_mask == 0
			){
				//ããã«ã©ã¼ãã¹ã¯ãææãã¦ããªãå ´åã¯
				//è¦å®ã®ã«ã©ã¼ãã¹ã¯ãé©ç¨ãã
				switch($bit_count){
				case 16:
					$red_mask   = 0x7c00;
					$green_mask = 0x03e0;
					$blue_mask  = 0x001f;
					break;
				case 24:
				case 32:
					$red_mask   = 0x00ff0000;
					$green_mask = 0x0000ff00;
					$blue_mask  = 0x000000ff;
					break;
				}
			}
		}
		
		if (
			($width  == 0)||
			($height == 0)||
			($planes != 1)||
			(($alpha_mask & $red_mask  ) != 0)||
			(($alpha_mask & $green_mask) != 0)||
			(($alpha_mask & $blue_mask ) != 0)||
			(($red_mask   & $green_mask) != 0)||
			(($red_mask   & $blue_mask ) != 0)||
			(($green_mask & $blue_mask ) != 0)
		){
			//ä¸æ­£ãªç»å
			return false;
		}

		//BI_JPEG ã¨ BI_PNG ã®å ´åã¯ jpeg/png ããã®ã¾ã¾å¥ã£ã¦ãã ããªã®ã§ãã®ã¾ã¾åãåºãã¦ãã³ã¼ããã
		if ($compression == 4 || $compression == 5){
			$buf = stream_get_contents($stream, $size_image);
			if ($buf === false){
				return false;
			}
			return imagecreatefromstring($buf);
		}

		//ç»åæ¬ä½ã®èª­ã¿åºã
		//1è¡ã®ãã¤ãæ°
		$line_bytes = (($width * $bit_count + 31) >> 3) & ~3;
		//å¨ä½ã®è¡æ°
		$lines = abs($height);
		//yè»¸é²è¡éï¼ããã ã¢ãããããããã¦ã³ãï¼
		$y = $height > 0 ? $lines-1 : 0;
		$line_step = $height > 0 ? -1 : 1;

		//256è²ä»¥ä¸ã®ç»åãï¼
		if ($bit_count == 1 || $bit_count == 4 || $bit_count == 8){
			$img = imagecreatetruecolor($width, $lines);

			//ç»åãã¼ã¿ã®åã«ãã¬ãããã¼ã¿ãããã®ã§ãã¬ãããä½æãã
			$palette_size = $header_size == 12 ? 3 : 4; //OS/2å½¢å¼ã®å ´åã¯ x ã«ç¸å½ããç®æã®ãã¼ã¿ã¯æåããç¢ºä¿ããã¦ããªã
			$colors = $clr_used ? $clr_used : pow(2, $bit_count); //è²æ°
			$palette = array();
			for($i = 0; $i < $colors; ++$i){
				$buf = fread($stream, $palette_size);
				if ($buf === false){
					imagedestroy($img);
					return false;
				}
				extract(unpack("Cb/Cg/Cr/Cx", $buf . "\x00"));
				$palette[] = imagecolorallocate($img, $r, $g, $b);
			}

			$shift_base = 8 - $bit_count;
			$mask = ((1 << $bit_count) - 1) << $shift_base;

			//å§ç¸®ããã¦ããå ´åã¨ããã¦ããªãå ´åã§ãã³ã¼ãå¦çãå¤§ããå¤ãã
			if ($compression == 1 || $compression == 2){
				$x = 0;
				$qrt_mod2 = $bit_count >> 2 & 1;
				for(;;){
					//ããæååãç¯å²å¤ã«ãªã£ã¦ããå ´åãã³ã¼ãå¦çããããããªã£ã¦ããã®ã§æãã
					//å¤ãªãã¼ã¿ãæ¸¡ãããã¨ãã¦ãææªãªã±ã¼ã¹ã§255åç¨åº¦ã®ç¡é§ãªã®ã§ç®ãçã
					if ($x < -1 || $x > $width || $y < -1 || $y > $height){
						imagedestroy($img);
						return false;
					}
					$buf = fread($stream, 1);
					if ($buf === false){
						imagedestroy($img);
						return false;
					}
					switch($buf){
					case "\x00":
						$buf = fread($stream, 1);
						if ($buf === false){
							imagedestroy($img);
							return false;
						}
						switch($buf){
						case "\x00": //EOL
							$y += $line_step;
							$x = 0;
							break;
						case "\x01": //EOB
							$y = 0;
							$x = 0;
							break 3;
						case "\x02": //MOV
							$buf = fread($stream, 2);
							if ($buf === false){
								imagedestroy($img);
								return false;
							}
							list(,$xx, $yy) = unpack("C2", $buf);
							$x += $xx;
							$y += $yy * $line_step;
							break;
						default:     //ABS
							list(,$pixels) = unpack("C", $buf);
							$bytes = ($pixels >> $qrt_mod2) + ($pixels & $qrt_mod2);
							$buf = fread($stream, ($bytes + 1) & ~1);
							if ($buf === false){
								imagedestroy($img);
								return false;
							}
							for ($i = 0, $pos = 0; $i < $pixels; ++$i, ++$x, $pos += $bit_count){
								list(,$c) = unpack("C", $buf[$pos >> 3]);
								$b = $pos & 0x07;
								imagesetpixel($img, $x, $y, $palette[($c & ($mask >> $b)) >> ($shift_base - $b)]);
							}
							break;
						}
						break;
					default:
						$buf2 = fread($stream, 1);
						if ($buf2 === false){
							imagedestroy($img);
							return false;
						}
						list(,$size, $c) = unpack("C2", $buf . $buf2);
						for($i = 0, $pos = 0; $i < $size; ++$i, ++$x, $pos += $bit_count){
							$b = $pos & 0x07;
							imagesetpixel($img, $x, $y, $palette[($c & ($mask >> $b)) >> ($shift_base - $b)]);
						}
						break;
					}
				}
			} else {
				for ($line = 0; $line < $lines; ++$line, $y += $line_step){
					$buf = fread($stream, $line_bytes);
					if ($buf === false){
						imagedestroy($img);
						return false;
					}

					$pos = 0;
					for ($x = 0; $x < $width; ++$x, $pos += $bit_count){
						list(,$c) = unpack("C", $buf[$pos >> 3]);
						$b = $pos & 0x7;
						imagesetpixel($img, $x, $y, $palette[($c & ($mask >> $b)) >> ($shift_base - $b)]);
					}
				}
			}
		} else {
			$img = imagecreatetruecolor($width, $lines);
			imagealphablending($img, false);
			if ($alpha_mask)
			{
				//Î±ãã¼ã¿ãããã®ã§ééæå ±ãä¿å­ã§ããããã«
				imagesavealpha($img, true);
			}

			//xè»¸é²è¡é
			$pixel_step = $bit_count >> 3;
			$alpha_max    = $alpha_mask ? 0x7f : 0x00;
			$alpha_mask_r = $alpha_mask ? 1/$alpha_mask : 1;
			$red_mask_r   = $red_mask   ? 1/$red_mask   : 1;
			$green_mask_r = $green_mask ? 1/$green_mask : 1;
			$blue_mask_r  = $blue_mask  ? 1/$blue_mask  : 1;

			for ($line = 0; $line < $lines; ++$line, $y += $line_step){
				$buf = fread($stream, $line_bytes);
				if ($buf === false){
					imagedestroy($img);
					return false;
				}

				$pos = 0;
				for ($x = 0; $x < $width; ++$x, $pos += $pixel_step){
					list(,$c) = unpack("V", substr($buf, $pos, $pixel_step). "\x00\x00");
					$a_masked = $c & $alpha_mask;
					$r_masked = $c & $red_mask;
					$g_masked = $c & $green_mask;
					$b_masked = $c & $blue_mask;
					
					$a = $alpha_max - ((($a_masked<<7) - $a_masked) * $alpha_mask_r);
					$r = (($r_masked<<8) - $r_masked) * $red_mask_r;
					$g = (($g_masked<<8) - $g_masked) * $green_mask_r;
					$b = (($b_masked<<8) - $b_masked) * $blue_mask_r;

					
					// debug
					#var_dump("<br>", dechex($r_masked>>16), dechex($g_masked>>8), dechex($b_masked));
					
					
					if ($bit_count == 16)
						imagesetpixel($img, $x, $y, ($a<<24)|($r<<16)|($g<<8)|$b);
					else
					{
						// HarpyWar: fix for 24/32 bit color
						$color = imagecolorallocate($img, $r_masked>>16, $g_masked>>8, $b_masked);
						imagesetpixel($img, $x, $y, $color);
					}
				}
			}
			imagealphablending($img, true); //ããã©ã«ãå¤ã«æ»ãã¦ãã
		}
		return $img;
	}
}
