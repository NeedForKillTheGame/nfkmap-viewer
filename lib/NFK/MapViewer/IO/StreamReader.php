<?php

/*
 * This file is part of NFK Map Viewer.
 *
 * (c) 2013 HarpyWar
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NFK\MapViewer\IO;

/**
 * Stream reader helper
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */
class StreamReader extends Stream
{
	public function getInt()
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
	public function getWord($long = false)
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
	
	public function getString($len)
	{
		return $this->read($len);
	}
	public function getChar()
	{
		return $this->read(1);
	}
	public function getByte()
	{
		return ord( $this->getChar() );
	}
	public function getBool()
	{
		return $this->getByte() === 1 ? true : false;
	}
}