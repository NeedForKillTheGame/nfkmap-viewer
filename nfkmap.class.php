<?php

// ----------------------------------------------------------
// NFK Map Viewer (Need For Kill game map format)
//
// Author: HarpyWar (harpywar@gmail.com)
// Webpage: http://harpywar.com
// Project page: https://github.com/HarpyWar/nfkmap-viewer
// Version: 24.01.2013
// Requirements: PHP >=5.3 with enabled extensions: php_gd2, php_bz2
// ----------------------------------------------------------

namespace NFK\MapViewer;

class MapViewer
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
	private $imres = array();
	
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


	// initializer
	public function __construct($filename)
	{
		$this->filename = $filename;
		
		// initial empty map
		$this->Header = new THeader();
	}

	
	// save map bytes to a mapa file
	public function SaveMap($filename = false)
	{
		if (!$filename)
			$filename = $this->getFileName();
	

		// write generated data to a file
		file_put_contents($filename . '.mapa', $this->GetMapBytes() );
	}
	
	
	// return map name from file name (without extension)
	public function GetFileName($filename = false)
	{
		if (!$filename)
			$filename = basename($this->filename);
			
		return preg_replace("/\\.[^.\\s]{3,4}$/", "", $filename);
	}
	

	
	// return palette image gd object
	public function GetPaletteImage()
	{
		if ( isset($this->imres['custom_palette']) )
			return $this->imres['custom_palette'];
		
		return false;
	}
	// return original palette bmp bytes
	public function GetPaletteBytes()
	{
		return $this->custom_palette_bin;
	}

	
	// return map image gd object
	public function GetMapImage()
	{
		return $this->image;
	}
	
	// generate and return map binary string
	public function GetMapBytes()
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
			throw new \Exception("Can't open file " . $this->filename);

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
			
			
			if ($this->debug)
				file_put_contents('decompressed.ndm', stream_get_contents($this->handle));
		}
		$this->pos = 0;
		$this->stream = '';
		
		// read header
		$this->Header = new THeader();
		
		$this->Header->ID = $this->getString(4);
		if ($this->Header->ID != "NMAP" && $this->Header->ID != "NDEM")
			throw new \Exception($this->filename . " is not NFK map/demo");

		$this->Header->Version = $this->getByte();
		if ($this->Header->Version < 3 || $this->Header->Version > 7)
			throw new \Exception("Incorrect map version");
		
		
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
			
		if ($this->debug)
			echo "Memory after LoadMap: " . memory_get_peak_usage() /1024/1024 . "<br>";

			
		return $this;
	}

	
	// draw map to $this->image
	public function DrawMap()
	{
		// load image resources
		$this->loadResources();

		if ($this->debug)
			echo "Memory after loadResources: " . memory_get_peak_usage() /1024/1024 . "<br>";

		
		$width = $this->Header->MapSizeX * $this->brick_w;
		$height = $this->Header->MapSizeY * $this->brick_h;


		// create map layer
		$this->image = imagecreatetruecolor($width, $height);
		
		if ($this->debug)
			echo "Memory after imagecreatetruecolor: " . memory_get_peak_usage() /1024/1024 . "<br>";
		
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
		
		if ($this->debug)
			echo "Memory after DrawMap: " . memory_get_peak_usage() /1024/1024 . "<br>";

		
		return $this->image;
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
		$pal_width = floor(imagesx($pal) / $this->brick_w);
		
		// find brick position
		$x = ($index % $pal_width) * $this->brick_w;
		$y = floor($index / $pal_width) * $this->brick_h;
		
		$brick = imagecreatetruecolor($this->brick_w, $this->brick_h); 
		
		// copy brick from the palette
		imagecopy($brick, $pal, 0, 0, $x, $y, $this->brick_w, $this->brick_h);
		
		// get transparent color of palette
		$color = imagecolortransparent($pal);
		
		#if ($this->debug)
		#	echo '<br>color: ' . $color;

		
		// set brick transparent color
		imagecolortransparent($brick, $color);
		
		
		if ($this->debug) // save each brick as single image
		{
			#@mkdir('bricks');
			#imagepng($brick, "bricks/$index.png");
		}
		
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
		// absolute path to the data dir
		$data_path = dirname(__FILE__) . '/data/';
	
		if ( !file_exists($data_path) || !is_dir($data_path) )
			die('Place "data" directory with the script');
			
		$this->imres['palette'] = imagecreatefrompng($data_path . 'palette.png');
		// set palette transparent color
		$color = imagecolorat($this->imres['palette'], 0, 0); // get first pixel color
		imagecolortransparent($this->imres['palette'], $color);

		if ($this->background !== false)
		{
			// if background index was not set then use MAP background
			$bg_filename = $data_path . 'bg_' . (($this->background !== null) ? $this->background : $this->Header->BG) . '.jpg';

			if ( file_exists( $bg_filename ) )
				$this->imres['bg'] = imagecreatefromjpeg($bg_filename);
		}
	
		$this->imres['portal'] = imagecreatefrompng($data_path . 'portal.png');
		$this->imres['door'] = imagecreatefrompng($data_path . 'door.png');
		$this->imres['button'] = imagecreatefrompng($data_path . 'button.png');
		
		if ($this->replacefineimages)
		{
			$this->imres['fine_armor'] = imagecreatefrompng($data_path . 'fine_armor.png');
			$this->imres['fine_flag'] = imagecreatefrompng($data_path . 'fine_flag.png');
			$this->imres['fine_power'] = imagecreatefrompng($data_path . 'fine_power.png');
			$this->imres['fine_mega'] = imagecreatefrompng($data_path . 'fine_mega.png');
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

// example: ff0000 -> 0000ff
function inverseHex( $hex )
{
	$newhex = array_reverse( str_split($hex, 2) );
	return implode($newhex);
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
 * bmp ファイルを GD で使えるように
 * 
 * 使用例:
 *   //ファイルから読み込む� �合はGDでPNGなどを読み込むのと同じような方法で可
 *   $image = imagecreatefrombmp("test.bmp");
 *   imagedestroy($image);
 * 
 *   //文字列から読み込む� �合は以下の方法で可
 *   $image = GdBmp::loadFromString(file_get_contents("test.bmp"));
 *   //自動判定されるので� �損ファイルでなければこれでも上手くいく
 *   //$image = imagecreatefrombmp(file_get_contents("test.bmp"));
 *   imagedestroy($image);
 * 
 *   //その他任意のストリー� からの読み込みも可能
 *   $stream = fopen("http://127.0.0.1/test.bmp");
 *   $image = GdBmp::loadFromStream($stream);
 *   //自動判定されるのでこれでもいい
 *   //$image = imagecreatefrombmp($stream);
 *   fclose($stream);
 *   imagedestroy($image);
 * 
 * 対応フォーマット
 *   1bit
 *   4bit
 *   4bitRLE
 *   8bit
 *   8bitRLE
 *   16bit(任意のビットフィールド)
 *   24bit
 *   32bit(任意のビットフィールド)
 *   BITMAPINFOHEADER の biCompression が BI_PNG / BI_JPEG の画像
 *   すべての形式でトップダウン/ボト� アップの両方をサポート
 *   特殊なビットフィールドでもビットフィールドデータが正常なら読み込み可能
 *
 * 以下のものは非対応
 *   BITMAPV4HEADER と BITMAPV5HEADER に含まれる色空間に関する様々な機能
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
		//data scheme より古いバージョンから対応しているようなので php://memory を使う
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

		//シグネチャチェック
		if ($buf[0] != 'B' || $buf[1] != 'M'){
			return false;
		}

		$bitmap_file_header = unpack(
			//BITMAPFILEHEADER構� 体
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

		//情� �ヘッダサイズを元に形式を区別して読み込み
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
				//BITMAPCOREHEADER構� 体 - OS/2 Bitmap
				"vwidth/".
				"vheight/".
				"vplanes/".
				"vbit_count", $buf
			));
			//飛んでこない分は 0 で初期化しておく
			$clr_used = $clr_important = $alpha_mask = $compression = 0;

			//マスク類は初期化されないのでここで割り当てておく
			$red_mask   = 0x00ff0000;
			$green_mask = 0x0000ff00;
			$blue_mask  = 0x000000ff;
		} else if (124 < $header_size || $header_size < 40) {
			//未知の形式
			return false;
		} else {
			//この時点で36バイト読めることまではわかっている
			$buf = fread($stream, 36); //既に読ん� 部分は除外しつつBITMAPINFOHEADERのサイズ� け読む
			if ($buf === false){
				return false;
			}

			//BITMAPINFOHEADER構� 体 - Windows Bitmap
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
			
			//� の整数を受け取る可能性があるものは自前で変換する
			if ($width  & 0x80000000){ $width  = -(~$width  & 0xffffffff) - 1; }
			if ($height & 0x80000000){ $height = -(~$height & 0xffffffff) - 1; }
			if ($x_pels_per_meter & 0x80000000){ $x_pels_per_meter = -(~$x_pels_per_meter & 0xffffffff) - 1; }
			if ($y_pels_per_meter & 0x80000000){ $y_pels_per_meter = -(~$y_pels_per_meter & 0xffffffff) - 1; }

			//ファイルによっては BITMAPINFOHEADER のサイズがおかしい（書き込み間違い？）ケースがある
			//自分でファイルサイズを元に逆算することで回避できることもあるので再計算できそうなら正当性を調べる
			//シークできないストリー� の� �合全体のファイルサイズは取得できないので、$bitmap_file_headerにサイズ申告がなければやらない
			if ($bitmap_file_header["size"] != 0){
				$colorsize = $bit_count == 1 || $bit_count == 4 || $bit_count == 8 ? ($clr_used ? $clr_used : pow(2, $bit_count))<<2 : 0;
				$bodysize = $size_image ? $size_image : ((($width * $bit_count + 31) >> 3) & ~3) * abs($height);
				$calcsize = $bitmap_file_header["size"] - $bodysize - $colorsize - 14;
				//本来であれば一致するはずなのに合わない時は、値がおかしくなさそうなら（BITMAPV5HEADERの範囲内なら）計算して求めた値を採用する
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
			
			//BITMAPV4HEADER や BITMAPV5HEADER の� �合ま� 読むべきデータが残っている可能性がある
			if ($header_size - 40 > 0){
				$buf = fread($stream, $header_size - 40);
				if ($buf === false){
					return false;
				}

				extract(unpack(
					//BITMAPV4HEADER構� 体(Windows95以降)
					//BITMAPV5HEADER構� 体(Windows98/2000以降)
					"Vred_mask/".
					"Vgreen_mask/".
					"Vblue_mask/".
					"Valpha_mask", $buf . str_repeat("\x00", 120)
				));
			} else {
				$alpha_mask = $red_mask = $green_mask = $blue_mask = 0;
			}

			//パレットがないがカラーマスクもない時
			if (
				($bit_count == 16 || $bit_count == 24 || $bit_count == 32)&&
				$compression == 0 &&
				$red_mask == 0 && $green_mask == 0 && $blue_mask == 0
			){
				//もしカラーマスクを所持していない� �合は
				//規定のカラーマスクを適用する
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
			//不正な画像
			return false;
		}

		//BI_JPEG と BI_PNG の� �合は jpeg/png がそのまま入ってる� けなのでそのまま取り出してデコードする
		if ($compression == 4 || $compression == 5){
			$buf = stream_get_contents($stream, $size_image);
			if ($buf === false){
				return false;
			}
			return imagecreatefromstring($buf);
		}

		//画像本体の読み出し
		//1行のバイト数
		$line_bytes = (($width * $bit_count + 31) >> 3) & ~3;
		//全体の行数
		$lines = abs($height);
		//y軸進行量（ボト� アップかトップダウンか）
		$y = $height > 0 ? $lines-1 : 0;
		$line_step = $height > 0 ? -1 : 1;

		//256色以下の画像か？
		if ($bit_count == 1 || $bit_count == 4 || $bit_count == 8){
			$img = imagecreatetruecolor($width, $lines);

			//画像データの前にパレットデータがあるのでパレットを作成する
			$palette_size = $header_size == 12 ? 3 : 4; //OS/2形式の� �合は x に相当する箇所のデータは最初から確保されていない
			$colors = $clr_used ? $clr_used : pow(2, $bit_count); //色数
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

			//圧縮されている� �合とされていない� �合でデコード処理が大きく変わる
			if ($compression == 1 || $compression == 2){
				$x = 0;
				$qrt_mod2 = $bit_count >> 2 & 1;
				for(;;){
					//もし描写先が範囲外になっている� �合デコード処理がおかしくなっているので抜ける
					//変なデータが渡されたとしても最悪なケースで255回程度の無駄なので目を瞑る
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
				//αデータがあるので透過情� �も保存できるように
				imagesavealpha($img, true);
			}

			//x軸進行量
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
			imagealphablending($img, true); //デフォルト値に戻しておく
		}
		return $img;
	}
}
