<?php

/*
 * This file is part of NFK Map Viewer.
 *
 * (c) 2013 HarpyWar
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NFK\MapViewer;


use NFK\MapViewer\IO\StreamReader;
use NFK\MapViewer\IO\StreamWriter;

use NFK\MapViewer\Type\THeader;
use NFK\MapViewer\Type\TLocationText;
use NFK\MapViewer\Type\TMapEntry;
use NFK\MapViewer\Type\TMapObj;

use NFK\MapViewer\GD\Graphics;

/**
 * Main class (read and write map)
 *
 * @package mapviewer
 * @version: 1.0.9
 * @author  HarpyWar <harpywar@gmail.com>
 */
class MapViewer
{
	/* --- SETUP START --- */
	
	// fill map background with repeated image
	//  value is an index of the image file "Data/bg_[index].jpg"
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
	
	// graphics object
	private $graphics;
	
	// palette transparent color
	private $transparent_color = false;
	
	// palette original image
	private $custom_palette_bin = false;

	
	
	// const
	private $tlocation_size = 68; // size in bytes of one TLocationText structure


	// initializer
	public function __construct($filename)
	{
		$this->filename = $filename;
		
		// initial empty map
		$this->Header = new THeader();
		

		$this->graphics = new Graphics();
		$this->graphics->background = &$this->background;
		$this->graphics->replacefineimages = &$this->replacefineimages;
		$this->graphics->drawlocations = &$this->drawlocations;
		$this->graphics->drawspecialobjects = &$this->drawspecialobjects;	
		$this->graphics->debug = &$this->debug;
		$this->graphics->Header = &$this->Header;
		$this->graphics->Bricks = &$this->Bricks;
		$this->graphics->Objects = &$this->Objects;
		$this->graphics->Locations = &$this->Locations;
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
		return $this->graphics->getResource('i_custom_palette');
	}
	// return original palette bmp bytes
	public function GetPaletteBytes()
	{
		return $this->custom_palette_bin;
	}

	
	// return map image gd object
	public function GetMapImage()
	{
		return $this->graphics->getImage();
	}
	
		// open and parse map file (or demo file)
	public function DrawMap()
	{
		return $this->graphics->DrawMap();
	}
	
	// open and parse map file (or demo file)
	public function LoadMap()
	{
		if ( !$data = @file_get_contents($this->filename) )
			throw new \Exception("Can't open file " . $this->filename);

		$reader = new StreamReader($data);

		// if file is a demo
		if ( $reader->getString(7) == "NFKDEMO")
		{
			$reader->getByte(); // unknown byte 0x2D (ignore)
			
			// read bz compressed data
			$data_bz = $reader->read(strlen($data) - 8);
			unset($reader);

			// decompress data and create new stream
			$data_bin = bzdecompress($data_bz);
			$reader = new StreamReader($data_bin);

			if ($this->debug)
				file_put_contents('decompressed.ndm', $data_bin);
		}

		// set read position to start
		$reader->rewind();

		// read header
		$this->Header = new THeader();
		
		$this->Header->ID = $reader->getString(4);
		if ($this->Header->ID != "NMAP" && $this->Header->ID != "NDEM")
			throw new \Exception($this->filename . " is not NFK map/demo");

		$this->Header->Version = $reader->getByte();
		if ($this->Header->Version < 3 || $this->Header->Version > 7)
			throw new \Exception("Incorrect map version");
		
		
		$b = $reader->getByte(); // size of next readable string
		$this->Header->MapName = substr($reader->getString(70), 0, $b);
		$b = $reader->getByte(); // size of next readable string
		$this->Header->Author = substr($reader->getString(70), 0, $b);
		
		$this->Header->MapSizeX = $reader->getByte();
		$this->Header->MapSizeY = $reader->getByte();
		
		$this->Header->BG = $reader->getByte();
		$this->Header->GAMETYPE = $reader->getByte();
		$this->Header->numobj = $reader->getByte();

		$this->Header->numlights = $reader->getWord();

		if ($this->debug)
			echo "<br>breaks start: " . $reader->pos() . " "; // 754
		
		// read bricks (start at pos 154)
		for ($y = 0; $y < $this->Header->MapSizeY; $y++)
			for ($x = 0; $x < $this->Header->MapSizeX; $x++)
				$this->Bricks[$x][$y] = $reader->getByte();
		
		if ($this->debug)
			echo "<br>objects start: " . $reader->pos() . " "; // 754
		
		// read objects
		for ($i = 0; $i < $this->Header->numobj; $i++)
		{
			$tmapobj = new TMapObj();
			$tmapobj->active = $reader->getBool();
				$reader->getByte(); // byte alignment (ignore)
				
			$tmapobj->x = $reader->getWord();
			$tmapobj->y = $reader->getWord();
			$tmapobj->length = $reader->getWord();
			$tmapobj->dir = $reader->getWord();
			$tmapobj->wait = $reader->getWord();
			$tmapobj->targetname = $reader->getWord();
			$tmapobj->target = $reader->getWord();
			$tmapobj->orient = $reader->getWord();
			$tmapobj->nowanim = $reader->getWord();
			$tmapobj->special = $reader->getWord();
			
			$tmapobj->objtype = $reader->getByte();
				$reader->getByte(); // byte alignment (ignore)
			
			$this->Objects[$i] = $tmapobj;
		}
		
		
		// read pal and loc blocks
		while ( strlen($reader->stream()) > $reader->pos() )
		{
			if ($this->debug)
				echo "<br>entry start: " . $reader->pos() . " ";
			
			$entry = new TMapEntry();
					
				$reader->getByte(); // 0x03 header of string (ignore)
			$entry->EntryType = $reader->getString(3);

			$entry->DataSize = $reader->getInt();
			$entry->Reserved1 = $reader->getByte();
			$entry->Reserved2 = $reader->getWord();
			$entry->Reserved3 = $reader->getInt();
			$entry->Reserved4 = $reader->getInt();
			$entry->Reserved5 = $reader->getInt(); // transparent color for palette
			$entry->Reserved6 = $reader->getBool(); // transparent value for palette
			
			if ($this->debug)
				print_r($entry);
			
			if ($entry->EntryType == 'pal')
			{
				if ($this->debug)
					echo "<br>pal start: " . $reader->pos();
				

				// image binary
				$pal_bz = $reader->getString($entry->DataSize);
				
				// decompress if map and nothing to do if demo file
				$this->custom_palette_bin = ($this->Header->ID == "NDEM")
								? $pal_bz
								: bzdecompress($pal_bz);
				
				// create gd object of palette
				if ( !$pal_gd = $this->graphics->imagecreatefrombmp($this->custom_palette_bin) )
					continue;
					
				$this->graphics->setResource('i_custom_palette', $pal_gd);

				
				// set transparent color if enabled
				if ($entry->Reserved6)
				{
					$this->transparent_color = $this->graphics->inversehexcolor( dechex($entry->Reserved5) );
					
					// set transparent color to gd object
					$color = $this->graphics->hexcoloralloc($this->graphics->getResource('i_custom_palette'), $this->transparent_color);

					imagecolortransparent($this->graphics->getResource('i_custom_palette'), $color);
				}
				
			
				if ($this->debug) // (save palette to file)
				{
					file_put_contents("palette_map.bmp", $this->custom_palette_bin); // original
					imagepng($this->graphics->getResource('i_custom_palette'), "palette_map.png"); // handled
				}
			}
			elseif ($entry->EntryType == 'loc')
			{
				if ($this->debug)
					echo "<br>loc start: " . $reader->pos();
			

				// fill locations
				for ($i = 0; $i < $entry->DataSize / $this->tlocation_size; $i++)
				{
					$loc = new TLocationText();
					
					$loc->enabled = $reader->getBool();
					$loc->x = $reader->getByte();
					$loc->y = $reader->getByte();
					$b = $reader->getByte(); // size of next readable string
					$loc->text = substr($reader->getString(64), 0, $b);
					
					$this->Locations[$i] = $loc;
				}
			}
			else // end of file
			{
				if ($this->debug)
					echo "<br>end of file: " . strlen($reader->stream()) . '|' . $reader->pos();
					
				// FIXME: not needed cause stream is different with original
				// remove TMapEntry length from the end of stream if file bigger than it should
				#if ( strlen($reader->stream()) == $reader->pos() )
				#	$reader->stream() = substr($reader->stream(), 0, strlen($reader->stream()) - 24);
				// fix header id and version in stream for map extracted from demo
				#if ($this->Header->ID == "NDEM")
				#	$reader->stream() = 'NMAP' . chr(3) . substr($reader->stream(), 5, strlen($reader->stream()) - 5);
		
				break;
			}
		}
		
		if ($this->debug)
			print_r($this);
			
		if ($this->debug)
			echo "Memory after LoadMap: " . memory_get_peak_usage() /1024/1024 . "<br>";

			
		return $this;
	}
	
	
	// generate and return map binary string
	public function GetMapBytes()
	{
		$writer = new StreamWriter();

		// write header
			$this->Header->ID = 'NMAP';
		$writer->putString($this->Header->ID);
			$this->Header->Version = 3;
		$writer->putByte($this->Header->Version);
		
		$writer->putByte( strlen($this->Header->MapName) ); // size of next readable string
		$writer->putString($this->Header->MapName, 70);
		$writer->putByte( strlen($this->Header->Author) ); // size of next readable string
		$writer->putString($this->Header->Author, 70);
		
		$writer->putByte($this->Header->MapSizeX);
		$writer->putByte($this->Header->MapSizeY);
		
		$writer->putByte($this->Header->BG);
		$writer->putByte($this->Header->GAMETYPE);
			$this->Header->numobj = count($this->Objects);
		$writer->putByte($this->Header->numobj);

		$writer->putWord($this->Header->numlights);
		
		
		// write bricks
		for ($y = 0; $y < $this->Header->MapSizeY; $y++)
			for ($x = 0; $x < $this->Header->MapSizeX; $x++)
				$writer->putByte( isset($this->Bricks[$x][$y]) ? $this->Bricks[$x][$y] : 0 );
		
		// write objects
		foreach($this->Objects as $tmapobj)
		{
			$writer->putBool($tmapobj->active);
				$writer->putByte(0x03); // byte alignment
				
			$writer->putWord($tmapobj->x);
			$writer->putWord($tmapobj->y);
			$writer->putWord($tmapobj->length);
			$writer->putWord($tmapobj->dir);
			$writer->putWord($tmapobj->wait);
			$writer->putWord($tmapobj->targetname);
			$writer->putWord($tmapobj->target);
			$writer->putWord($tmapobj->orient);
			$writer->putWord($tmapobj->nowanim);
			$writer->putWord($tmapobj->special);
			
			$writer->putByte($tmapobj->objtype);
				$writer->putByte(0x03); // byte alignment
		}
		
		// write map palette
		if ( $this->graphics->getResource('i_custom_palette') )
		{
			$pal_bz = bzcompress($this->custom_palette_bin);
			
			// entry
			$writer->putByte(0x03); // 0x03 header of string
			$writer->putString('pal'); 
			
			$writer->putInt(strlen($pal_bz)); // bmp size
			$writer->putByte(0);
			$writer->putWord(0);
			$writer->putInt(0);
			$writer->putInt(0);
			$writer->putInt($this->transparent_color ? hexdec($this->transparent_color) : 0); // transparent color for palette
			$writer->putBool($this->transparent_color ? true : false); // transparent value for palette
			
			$writer->putString($pal_bz); 
		}

		// write locations
		if ($this->Locations && count($this->Locations) > 0)
		{
				// entry
				$writer->putByte(0x03); // 0x03 header of string
				$writer->putString('loc'); 
				
				$writer->putInt(count($this->Locations) * $this->tlocation_size); // locations size
				$writer->putByte(0);
				$writer->putWord(0);
				$writer->putInt(0);
				$writer->putInt(0);
				$writer->putInt(0);
				$writer->putBool(false);
				
				foreach($this->Locations as $loc)
				{
					$writer->putBool($loc->enabled);
					$writer->putByte($loc->x);
					$writer->putByte($loc->y);
					$writer->putByte( strlen($loc->text) ); // size of next readable string
					$writer->putString($loc->text, 64);
				}
		}
		
		return $writer->stream();
	}
	


	
	public function __destruct()
	{
		// free resources
		unset($this->graphics);
	}
}

