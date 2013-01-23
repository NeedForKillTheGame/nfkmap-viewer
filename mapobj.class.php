<?php

// [Map Object Helper]
// It will help you with map editing

// (c) 2013, HarpyWar


namespace NFK\MapViewer;

// -- SPECIAL OBJECTS --
//
// each method return object of TMapObj
// example call:
//		$obj = SpecialObject::Teleport(12, 3, 0, 0);
//
// use next link as manual https://github.com/HarpyWar/nfkmap-viewer/wiki/%D0%A1%D0%BF%D0%B5%D1%86%D0%B8%D0%B0%D0%BB%D1%8C%D0%BD%D1%8B%D0%B5-%D0%BE%D0%B1%D1%8A%D0%B5%D0%BA%D1%82%D1%8B-%D0%BD%D0%B0-%D0%BA%D0%B0%D1%80%D1%82%D0%B5
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


// -- SIMPLE OBJECTS --
//
// each method return just a brick number
// example call:
//		$brick = SpecialObject::Shotgun();
interface ISimpleObject
{
	// items
	public static function Shotgun();
	public static function GrenadeLauncher();
	public static function RocketLauncher();
	public static function Shaft();
	public static function Railgun();
	public static function Plasmagun();
	public static function Bfg();
	public static function AmmoMachinegun();
	public static function AmmoShotgun();
	public static function AmmoGrenadeLauncher();
	public static function AmmoRocketLauncher();
	public static function AmmoShaft();
	public static function AmmoRailgun();
	public static function AmmoPlasmagun();
	public static function AmmoBfg();
	public static function Shard(); // +5 armor
	public static function YellowArmor();
	public static function RedArmor();
	public static function Health5();
	public static function Health25();
	public static function Health50();
	public static function MegaHealth();
	public static function PowerupRegeneration();
	public static function PowerupBattlesuit();
	public static function PowerupHaste();
	public static function PowerupQuaddamage();
	public static function PowerupFlight();
	public static function PowerupInvisibility();
	public static function TrixGrenadeLauncher();
	public static function TrixRocketLauncher();
	
	// objects
	public static function Lava();
	public static function Water();
	public static function Death();
	public static function Respawn();
	public static function RedRespawn();
	public static function BlueRespawn();
	public static function EmptyBrick();
	public static function Jumppad();
	public static function StrongJumppad();
	public static function RedFlag();
	public static function BlueFlag();
	public static function DominationPoint();
}





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
		$obj->orient = $color;
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





// each method return just a brick number
class SimpleObject implements ISimpleObject
{
	public static function Shotgun()
	{
		return 1;
	}

	public static function GrenadeLauncher()
	{
		return 2;
	}

	public static function RocketLauncher()
	{
		return 3;
	}

	public static function Shaft()
	{
		return 4;
	}

	public static function Railgun()
	{
		return 5;
	}

	public static function Plasmagun()
	{
		return 6;
	}

	public static function Bfg()
	{
		return 7;
	}

	public static function AmmoMachinegun()
	{
		return 8;
	}

	public static function AmmoShotgun()
	{
		return 9;
	}

	public static function AmmoGrenadeLauncher()
	{
		return 10;
	}

	public static function AmmoRocketLauncher()
	{
		return 11;
	}

	public static function AmmoShaft()
	{
		return 12;
	}

	public static function AmmoRailgun()
	{
		return 13;
	}

	public static function AmmoPlasmagun()
	{
		return 14;
	}

	public static function AmmoBfg()
	{
		return 15;
	}

	public static function Shard()
	{
		return 16;
	}

	public static function YellowArmor()
	{
		return 17;
	}

	public static function RedArmor()
	{
		return 18;
	}

	public static function Health5()
	{
		return 19;
	}

	public static function Health25()
	{
		return 20;
	}

	public static function Health50()
	{
		return 21;
	}

	public static function MegaHealth()
	{
		return 22;
	}

	public static function PowerupRegeneration()
	{
		return 23;
	}

	public static function PowerupBattlesuit()
	{
		return 24;
	}

	public static function PowerupHaste()
	{
		return 25;
	}

	public static function PowerupQuaddamage()
	{
		return 26;
	}

	public static function PowerupFlight()
	{
		return 27;
	}

	public static function PowerupInvisibility()
	{
		return 28;
	}

	public static function TrixGrenadeLauncher()
	{
		return 29;
	}

	public static function TrixRocketLauncher()
	{
		return 30;
	}


	public static function Lava()
	{
		return 31;
	}

	public static function Water()
	{
		return 32;
	}

	public static function Death()
	{
		return 33;
	}

	public static function Respawn()
	{
		return 34;
	}

	public static function RedRespawn()
	{
		return 35;
	}

	public static function BlueRespawn()
	{
		return 36;
	}

	public static function EmptyBrick()
	{
		return 37;
	}

	public static function Jumppad()
	{
		return 38;
	}

	public static function StrongJumppad()
	{
		return 39;
	}

	public static function RedFlag()
	{
		return 40;
	}

	public static function BlueFlag()
	{
		return 41;
	}

	public static function DominationPoint()
	{
		return 42;
	}
}

