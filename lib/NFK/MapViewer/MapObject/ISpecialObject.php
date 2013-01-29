<?php

/*
 * This file is part of NFK Map Viewer.
 *
 * (c) 2013 HarpyWar
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NFK\MapViewer\MapObject;

/**
 * SPECIAL OBJECTS
 *  each method return object of TMapObj
 *
 *	example call:
 *		$obj = SpecialObject::Teleport(12, 3, 0, 0);
 * use next link as manual https://github.com/HarpyWar/nfkmap-viewer/wiki/Специальные-объекты-на-карте
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */
interface ISpecialObject
{
	public static function Teleport($x, $y, $goto_x, $goto_y);
	public static function Button($x, $y, $color, $wait, $target, $shootable);
	public static function Door($x, $y, $orient, $length, $wait, $targetname, $fastclose);
	public static function Trigger($x, $y, $length_x, $length_y, $wait, $target);
	public static function AreaPush($x, $y, $length_x, $length_y, $wait, $target, $direction, $pushspeed);
	public static function AreaPain($x, $y, $length_x, $length_y, $wait, $dmginterval, $dmg);
	public static function AreaTrixarenaEnd($x, $y, $length_x, $length_y);
	public static function AreaTeleport($x, $y, $length_x, $length_y, $goto_x, $goto_y);
	public static function DoorTrigger($x, $y, $orient, $length, $target);
	public static function AreaWaterillusion($x, $y, $length_x, $length_y);
}

