<?php

/*
 * This file is part of NFK Map Viewer.
 *
 * (c) 2013 HarpyWar
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NFK\MapViewer\GD;

/**
 * Draw map on GD layer
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */
class Graphics extends Resource
{
	// final map gd object that can be saved
	private $image;
	
	
	// const
	private $brick_w = 32;
	private $brick_h = 16;
	
	
	// these variables are the same as in MapViewer class
	//  and passed by reference from there
	public $background = null;
	public $replacefineimages = true;
	public $drawlocations = false;
	public $drawspecialobjects = true;	
	public $debug = false;
	public $Header;
	public $Bricks = array();
	public $Objects = array();
	public $Locations = array();
		
		
	// return map image
	public function getImage()
	{
		return $this->image;
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
		if ( $this->getResource('i_bg') )
			for ($x = 0; $x < imagesx($this->image) / imagesx($this->getResource('i_bg')); $x++ )
				for ($y = 0; $y < imagesy($this->image) / imagesy($this->getResource('i_bg')); $y++ )
					imagecopy($this->image, $this->getResource('i_bg'), $x * imagesx($this->getResource('i_bg')), $y * imagesy($this->getResource('i_bg')), 0, 0, imagesx($this->getResource('i_bg')), imagesy($this->getResource('i_bg')));

		
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
					imagecopy($this->image, $this->getResource('i_portal'), 
						$obj->x * $this->brick_w - $this->brick_w / 2, 
						$obj->y * $this->brick_h - $this->brick_h * 2, 
						0, 
						0, 
						imagesx($this->getResource('i_portal')), imagesy($this->getResource('i_portal')) );

					// draw arrow to goto position
					if ($this->drawspecialobjects)
						$this->drawarrow($this->image, 
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
					
					imagecopy($this->image, $this->getResource('i_button'), 
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
								$this->drawarrow($this->image, 
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
						imagecopy($this->image, $this->getResource('i_door'), 
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
		$pal = $this->getResource('i_palette');

		if ($index >= 54 && $index <= 181)
		{
			if ( $this->getResource('i_custom_palette') )
			{
				$pal = $this->getResource('i_custom_palette');
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
				imagecopy($this->image, $this->getResource('i_fine_armor'), 
					$x * $this->brick_w, 
					$y * $this->brick_h, 
					0, 0, 
					$this->brick_w, $this->brick_h);
				break;
				
			// red armor
			case 18:
				imagecopy($this->image, $this->getResource('i_fine_armor'), 
					$x * $this->brick_w, 
					$y * $this->brick_h, 
					$this->brick_w, 0, 
					$this->brick_w, $this->brick_h);
				break;
				
			// megahealth
			case 22:
				$size = 24;
				imagecopy($this->image, $this->getResource('i_fine_mega'), 
					$x * $this->brick_w + ($this->brick_w - $size - 1) / 2, 
					$y * $this->brick_h - ($size - $this->brick_h - 1), 
					0, 0, 
					$size, $size);
				break;
				
			// regeneration
			case 23:
				$this->_drawFineItem('i_fine_power', $x, $y, 2);
				break;
				
			// battlesuite
			case 24:
				$this->_drawFineItem('i_fine_power', $x, $y, 3);
				break;
				
			// haste
			case 25:
				$this->_drawFineItem('i_fine_power', $x, $y, 0);
				break;
				
			// quaddamage
			case 26:
				$this->_drawFineItem('i_fine_power', $x, $y, 4);
				break;
				
			// flight
			case 27:
				$this->_drawFineItem('i_fine_power', $x, $y, 5);
				break;
				
			// invisibility
			case 28:
				$this->_drawFineItem('i_fine_power', $x, $y, 1);
				break;

			// respawns
			case 34: // default
			case 35: // red
			case 36: // blue
				$src_x = abs(34 - $index) * $this->brick_w;
				$im = $this->getResource('i_fine_respawn');
				
				// flip respawn horizontally if it's in the right half of the map
				if ($x > $this->Header->MapSizeX / 2)
				{
					$size_x = imagesx($im);
					$size_y = imagesy($im);
				
					$tmp = imagecreatetruecolor($size_x, $size_y);
					imagecolortransparent($tmp, imagecolorallocate($im, 0, 0, 0));
					imagealphablending($tmp, false);
					imagesavealpha($tmp, true);
					
					imagecopyresampled($tmp, $im, 0, 0, ($size_x-1), 0, $size_x, $size_y, 0-$size_x, $size_y);
					
					$im = $tmp;
					$src_x = (36 - $index) * $this->brick_w;
				}
				
				imagecopy($this->image, $im, 
					$x * $this->brick_w, 
					$y * $this->brick_h - $this->brick_h * 2, 
					$src_x, 0, 
					$this->brick_w, $this->brick_h * 3);
				break;
				
				
			// blue flag
			case 40:
				$size_x = 36;
				$size_y = 41;
				imagecopy($this->image, $this->getResource('i_fine_flag'), 
					$x * $this->brick_w + ($this->brick_w - $size_x) / 2, 
					$y * $this->brick_h - ($size_y - $this->brick_h), 
					0, 0, 
					$size_x, $size_y);
				break;
				
			// red flag
			case 41:
				$size_x = 36;
				$size_y = 41;
				imagecopy($this->image, $this->getResource('i_fine_flag'), 
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
		imagecopy($this->image, $this->getResource($res), $x * $this->brick_w, 
					$y * $this->brick_h - $this->brick_h, $index * 37, 0, 37, 32);
	}
	

	
	// preload image resources
	public function loadResources()
	{
		// absolute path to the data dir
		$data_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Resource' . DIRECTORY_SEPARATOR;
	
		if ( !file_exists($data_path) || !is_dir($data_path) )
			die('"Resource" directory not found (' . $data_path . ')');
			
		$this->setResource('i_palette', imagecreatefrompng($data_path . 'palette.png'));
		// set bricks palette transparent color
		$color = imagecolorat($this->getResource('i_palette'), 0, 0); // get first pixel color
		imagecolortransparent($this->getResource('i_palette'), $color);

		if ($this->background !== false)
		{
			// if background index was not set then use MAP background
			$bg_filename = $data_path . 'bg_' . (($this->background !== null) ? $this->background : $this->Header->BG) . '.jpg';

			if ( file_exists( $bg_filename ) )
				$this->setResource('i_bg', imagecreatefromjpeg($bg_filename));
		}
	
		$this->setResource('i_portal', imagecreatefrompng($data_path . 'portal.png'));
		$this->setResource('i_door', imagecreatefrompng($data_path . 'door.png'));
		$this->setResource('i_button', imagecreatefrompng($data_path . 'button.png'));
		
		if ($this->replacefineimages)
		{
			$this->setResource('i_fine_armor', imagecreatefrompng($data_path . 'fine_armor.png'));
			$this->setResource('i_fine_flag', imagecreatefrompng($data_path . 'fine_flag.png'));
			$this->setResource('i_fine_power', imagecreatefrompng($data_path . 'fine_power.png'));
			$this->setResource('i_fine_mega', imagecreatefrompng($data_path . 'fine_mega.png'));
			$this->setResource('i_fine_respawn', imagecreatefrompng($data_path . 'fine_respawn.png'));
		}
	}
	
	
	
		// draw arrow
	public function drawarrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color)
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
	
	
	public function hexcoloralloc($im, $hex)
	{ 
	  $a = hexdec(substr($hex,0,2)); 
	  $b = hexdec(substr($hex,2,2)); 
	  $c = hexdec(substr($hex,4,2)); 

	  return imagecolorallocate($im, $a, $b, $c); 
	} 

	// example: ff0000 -> 0000ff
	public function inversehexcolor( $hex )
	{
		$newhex = array_reverse( str_split($hex, 2) );
		return implode($newhex);
	}

	public function imagecreatefrombmp($filename_or_stream_or_binary)
	{
		return GdBmp::load($filename_or_stream_or_binary);
	}

	
	public function __destruct()
	{
		// destroy map image layer
		if ($this->image)
			imagedestroy($this->image);
			
		// free resources
		if ($this->resource)
			foreach ($this->resource as $r)
				if ( get_resource_type($r) == 'gd' )
					@imagedestroy($r);
				else
					unset($r);
	}
}
