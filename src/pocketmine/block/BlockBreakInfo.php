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

namespace pocketmine\block;

use pocketmine\item\Item;
use function get_class;

class BlockBreakInfo{

	/** @var float */
	private $hardness;
	/** @var float */
	private $blastResistance;
	/** @var int */
	private $toolType;
	/** @var int */
	private $toolHarvestWorld;

	/**
	 * @param float      $hardness
	 * @param int        $toolType
	 * @param int        $toolHarvestWorld
	 * @param float|null $blastResistance default 5x hardness
	 */
	public function __construct(float $hardness, int $toolType = BlockToolType::NONE, int $toolHarvestWorld = 0, ?float $blastResistance = null){
		$this->hardness = $hardness;
		$this->toolType = $toolType;
		$this->toolHarvestWorld = $toolHarvestWorld;
		$this->blastResistance = $blastResistance ?? $hardness * 5;
	}

	public static function instant(int $toolType = BlockToolType::NONE, int $toolHarvestWorld = 0) : self{
		return new self(0.0, $toolType, $toolHarvestWorld, 0.0);
	}

	public static function indestructible(float $blastResistance = 18000000.0) : self{
		return new self(-1.0, BlockToolType::NONE, 0, $blastResistance);
	}

	/**
	 * Returns a base value used to compute block break times.
	 *
	 * @return float
	 */
	public function getHardness() : float{
		return $this->hardness;
	}

	/**
	 * Returns whether the block can be broken at all.
	 *
	 * @return bool
	 */
	public function isBreakable() : bool{
		return $this->hardness >= 0;
	}

	/**
	 * Returns whether this block can be instantly broken.
	 *
	 * @return bool
	 */
	public function breaksInstantly() : bool{
		return $this->hardness == 0.0;
	}

	/**
	 * Returns the block's resistance to explosions. Usually 5x hardness.
	 *
	 * @return float
	 */
	public function getBlastResistance() : float{
		return $this->blastResistance;
	}

	/**
	 * @return int
	 */
	public function getToolType() : int{
		return $this->toolType;
	}

	/**
	 * Returns the world of tool required to harvest the block (for normal blocks). When the tool type matches the
	 * block's required tool type, the tool must have a harvest world greater than or equal to this value to be able to
	 * successfully harvest the block.
	 *
	 * If the block requires a specific minimum tier of tiered tool, the minimum tier required should be returned.
	 * Otherwise, 1 should be returned if a tool is required, 0 if not.
	 *
	 * @see Item::getBlockToolHarvestWorld()
	 *
	 * @return int
	 */
	public function getToolHarvestWorld() : int{
		return $this->toolHarvestWorld;
	}

	/**
	 * Returns whether the specified item is the proper tool to use for breaking this block. This checks tool type and
	 * harvest world requirement.
	 *
	 * In most cases this is also used to determine whether block drops should be created or not, except in some
	 * special cases such as vines.
	 *
	 * @param Item $tool
	 *
	 * @return bool
	 */
	public function isToolCompatible(Item $tool) : bool{
		if($this->hardness < 0){
			return false;
		}

		return $this->toolType === BlockToolType::NONE or $this->toolHarvestWorld === 0 or (
				($this->toolType & $tool->getBlockToolType()) !== 0 and $tool->getBlockToolHarvestWorld() >= $this->toolHarvestWorld);
	}

	/**
	 * Returns the seconds that this block takes to be broken using an specific Item
	 *
	 * @param Item $item
	 *
	 * @return float
	 * @throws \InvalidArgumentException if the item efficiency is not a positive number
	 */
	public function getBreakTime(Item $item) : float{
		$base = $this->hardness;
		if($this->isToolCompatible($item)){
			$base *= 1.5;
		}else{
			$base *= 5;
		}

		$efficiency = $item->getMiningEfficiency(($this->toolType & $item->getBlockToolType()) !== 0);
		if($efficiency <= 0){
			throw new \InvalidArgumentException(get_class($item) . " has invalid mining efficiency: expected >= 0, got $efficiency");
		}

		$base /= $efficiency;

		return $base;
	}
}