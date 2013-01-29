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
 * Type of Map Location object
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */
class TLocationText
{
	public $enabled = 0; // boolean
	public $x = 0, $y = 0; // byte
	public $text = ''; // string[64]
}
