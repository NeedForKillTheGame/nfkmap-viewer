<?php

/*
 * This file is part of NFK Map Viewer.
 *
 * (c) 2013 HarpyWar
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NFK\MapViewer\Type;

/**
 * Type of special Map Object
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */
class TMapObj
{
	public $active = 0; // boolean
	public $x = 0, $y = 0, $length = 0, $dir = 0, $wait = 0; // word
	public $targetname = 0, $target = 0, $orient = 0, $nowanim = 0, $special = 0; // word
	public $objtype = 0; // byte
}