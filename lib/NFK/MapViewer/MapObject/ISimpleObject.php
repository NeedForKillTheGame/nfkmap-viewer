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
 * SIMPLE OBJECTS
 *  each method return just a brick number
 *
 * @package mapviewer
 * @author  HarpyWar <harpywar@gmail.com>
 */	
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