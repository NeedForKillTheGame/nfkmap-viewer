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

use NFK\MapViewer\Type\TMapObj;

/**
 * Special objects helper (see interface for more info)
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */
class SpecialObject implements ISpecialObject
{
	public static function Teleport($x, $y, $goto_x, $goto_y)
	{
		$obj = new TMapObj();
		$obj->objtype = 1;
		$obj->active = 1;
		
		$obj->x = $x;
		$obj->y = $y;
		$obj->length = $goto_x;
		$obj->dir = $goto_y;

		return $obj;
	}

	public static function Button($x, $y, $color, $wait, $target, $shootable)
	{
		$obj = new TMapObj();
		$obj->objtype = 2;
		$obj->active = 1;
		
		$obj->x = $x;
		$obj->y = $y;
		$obj->orient = $color;
		$obj->wait = $wait;
		$obj->target = $target;
		$obj->special = $shootable;

		return $obj;
	}

	public static function Door($x, $y, $orient, $length, $wait, $targetname, $fastclose)
	{
		$obj = new TMapObj();
		$obj->objtype = 3;
		$obj->active = 1;
		
		$obj->x = $x;
		$obj->y = $y;
		$obj->orient = $orient;
		$obj->length = $length;		
		$obj->wait = $wait;
		$obj->targetname = $targetname;
		$obj->special = $fastclose;

		return $obj;
	}

	public static function Trigger($x, $y, $length_x, $length_y, $wait, $target)
	{
		$obj = new TMapObj();
		$obj->objtype = 4;
		$obj->active = 1;
		
		$obj->x = $x;
		$obj->y = $y;
		$obj->length = $length_x;
		$obj->dir = $length_y;		
		$obj->wait = $wait;
		$obj->target = $target;

		return $obj;
	}

	public static function AreaPush($x, $y, $length_x, $length_y, $wait, $target, $direction, $pushspeed)
	{
		$obj = new TMapObj();
		$obj->objtype = 5;
		$obj->active = 1;
		
		$obj->x = $x;
		$obj->y = $y;
		$obj->length = $length_x;
		$obj->dir = $length_y;
		$obj->wait = $wait;
		$obj->target = $target;
		$obj->orient = $direction;
		$obj->special = $pushspeed;

		return $obj;
	}

	public static function AreaPain($x, $y, $length_x, $length_y, $wait, $dmginterval, $dmg)
	{
		$obj = new TMapObj();
		$obj->objtype = 6;
		$obj->active = 1;
		
		$obj->x = $x;
		$obj->y = $y;
		$obj->special = $length_x;
		$obj->orient = $length_y;
		$obj->wait = $wait;
		$obj->nowanim = $dmginterval;
		$obj->dir = $dmg;

		return $obj;
	}

	public static function AreaTrixarenaEnd($x, $y, $length_x, $length_y)
	{
		$obj = new TMapObj();
		$obj->objtype = 7;
		$obj->active = 1;
		
		$obj->x = $x;
		$obj->y = $y;
		$obj->special = $length_x;
		$obj->orient = $length_y;

		return $obj;
	}

	public static function AreaTeleport($x, $y, $length_x, $length_y, $goto_x, $goto_y)
	{
		$obj = new TMapObj();
		$obj->objtype = 1;
		$obj->active = 1;
		
		$obj->x = $x;
		$obj->y = $y;
		$obj->dir = $length_x;
		$obj->wait = $length_y;
		$obj->special = $goto_x;
		$obj->orient = $goto_y;

		return $obj;
	}

	public static function DoorTrigger($x, $y, $orient, $length, $target)
	{
		$obj = new TMapObj();
		$obj->objtype = 1;
		$obj->active = 1;
		
		$obj->x = $x;
		$obj->y = $y;
		$obj->orient = $orient;
		$obj->length = $length;
		$obj->target = $target;

		return $obj;
	}

	public static function AreaWaterillusion($x, $y, $length_x, $length_y)
	{
		$obj = new TMapObj();
		$obj->objtype = 1;
		$obj->active = 1;
		
		$obj->x = $x;
		$obj->y = $y;
		$obj->special = $length_x;
		$obj->orient = $length_y;

		return $obj;
	}
}
