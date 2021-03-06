<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\inventory;

use pocketmine\item\Durable;
use pocketmine\item\Item;
use function file_get_contents;
use function json_decode;
use const DIRECTORY_SEPARATOR;

final class CreativeInventory{

	/** @var Item[] */
	public static $creative = [];

	private function __construct(){
		//NOOP
	}

	public static function init(){
		self::clear();

		$creativeItems = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "vanilla" . DIRECTORY_SEPARATOR . "creativeitems.json"), true);

		foreach($creativeItems as $data){
			$item = Item::jsonDeserialize($data);
			if($item->getName() === "Unknown"){
				continue;
			}
			self::add($item);
		}
	}

	public static function clear(){
		self::$creative = [];
	}

	/**
	 * @return Item[]
	 */
	public static function getAll() : array{
		return self::$creative;
	}

	/**
	 * @param int $index
	 *
	 * @return Item|null
	 */
	public static function getItem(int $index) : ?Item{
		return self::$creative[$index] ?? null;
	}

	public static function getItemIndex(Item $item) : int{
		foreach(self::$creative as $i => $d){
			if($item->equals($d, !($item instanceof Durable))){
				return $i;
			}
		}

		return -1;
	}

	public static function add(Item $item){
		self::$creative[] = clone $item;
	}

	public static function remove(Item $item){
		$index = self::getItemIndex($item);
		if($index !== -1){
			unset(self::$creative[$index]);
		}
	}

	public static function contains(Item $item) : bool{
		return self::getItemIndex($item) !== -1;
	}
}
