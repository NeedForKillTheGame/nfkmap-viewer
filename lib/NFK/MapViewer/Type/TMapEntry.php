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
 * Type of Map Entry object (palette and location)
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */
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
