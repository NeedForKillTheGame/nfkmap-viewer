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
 * Stream writer helper
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */
class StreamWriter extends Stream
{
	public function putInt($value)
	{
		// convert to int with writing little endian
		$value = pack("V*", $value);
					
		$this->write($value);
	}
	
	public function putWord($value, $long = false)
	{
		// convert to word with writing little endian
		$value = !$long
					? pack("s*", $value)
					: pack("S*", $value);
					
		$this->write($value);
	}
	public function putString($value, $fix_len = false)
	{
		// fill string to fixed length
		if ($fix_len)
			$value = str_pad($value, $fix_len, chr(0), STR_PAD_RIGHT);
		
		$this->write($value);
	}
	public function putChar($value)
	{
		$this->write($value);
	}
	public function putByte($value)
	{
		$this->putChar( chr($value) );
	}
	public function putBool($value)
	{
		$this->putByte($value ? '1' : '0');
	}
}