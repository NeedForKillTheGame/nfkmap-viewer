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
 * Resource object
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */
class Resource
{
	// array with resourceses
	protected $resource;
	
	// get resource value by key
	public function getResource($key)
	{
		if ( $this->resource && array_key_exists($key, $this->resource) )
			return $this->resource[$key];
		
		return false;
	}
	// set resource value
	public function setResource($key, $value)
	{
		$this->resource[$key] = $value;
	}
	
	/*
	// set resource values from array
	public function setResource($arr)
	{
		if ( is_array($value) )
			foreach($value as $key => $value)
				$this->resource[$key] = $value;
	}
	*/
}