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

namespace pocketmine\entity;

use function max;
use function min;

class Attribute{
	public const MC_PREFIX = "minecraft:";

	public const ABSORPTION = self::MC_PREFIX . "absorption";
	public const SATURATION = self::MC_PREFIX . "player.saturation";
	public const EXHAUSTION = self::MC_PREFIX . "player.exhaustion";
	public const KNOCKBACK_RESISTANCE = self::MC_PREFIX . "knockback_resistance";
	public const HEALTH = self::MC_PREFIX . "health";
	public const MOVEMENT_SPEED = self::MC_PREFIX . "movement";
	public const FOLLOW_RANGE = self::MC_PREFIX . "follow_range";
	public const HUNGER = self::MC_PREFIX . "player.hunger";
	public const FOOD = self::HUNGER;
	public const ATTACK_DAMAGE = self::MC_PREFIX . "attack_damage";
	public const EXPERIENCE_LEVEL = self::MC_PREFIX . "player.level";
	public const EXPERIENCE = self::MC_PREFIX . "player.experience";
	public const UNDERWATER_MOVEMENT = self::MC_PREFIX . "underwater_movement";
	public const LUCK = self::MC_PREFIX . "luck";
	public const FALL_DAMAGE = self::MC_PREFIX . "fall_damage";
	public const HORSE_JUMP_STRENGTH = self::MC_PREFIX . "horse.jump_strength";
	public const ZOMBIE_SPAWN_REINFORCEMENTS = self::MC_PREFIX . "zombie.spawn_reinforcements";

	protected $id;
	protected $minValue;
	protected $maxValue;
	protected $defaultValue;
	protected $currentValue;
	protected $shouldSend;

	protected $desynchronized = true;

	/** @var Attribute[] */
	protected static $attributes = [];

	public static function init() : void{
		self::addAttribute(self::ABSORPTION, 0.00, 340282346638528859811704183484516925440.00, 0.00);
		self::addAttribute(self::SATURATION, 0.00, 20.00, 20.00);
		self::addAttribute(self::EXHAUSTION, 0.00, 5.00, 0.0, false);
		self::addAttribute(self::KNOCKBACK_RESISTANCE, 0.00, 1.00, 0.00);
		self::addAttribute(self::HEALTH, 0.00, 20.00, 20.00);
		self::addAttribute(self::MOVEMENT_SPEED, 0.00, 340282346638528859811704183484516925440.00, 0.10);
		self::addAttribute(self::FOLLOW_RANGE, 0.00, 2048.00, 16.00, false);
		self::addAttribute(self::HUNGER, 0.00, 20.00, 20.00);
		self::addAttribute(self::ATTACK_DAMAGE, 0.00, 340282346638528859811704183484516925440.00, 1.00, false);
		self::addAttribute(self::EXPERIENCE_LEVEL, 0.00, 24791.00, 0.00);
		self::addAttribute(self::EXPERIENCE, 0.00, 1.00, 0.00);
		self::addAttribute(self::UNDERWATER_MOVEMENT, 0.0, 340282346638528859811704183484516925440.0, 0.02);
		self::addAttribute(self::LUCK, -1024.0, 1024.0, 0.0);
		self::addAttribute(self::FALL_DAMAGE, 0.0, 340282346638528859811704183484516925440.0, 1.0);
		self::addAttribute(self::HORSE_JUMP_STRENGTH, 0.0, 2.0, 0.7);
		self::addAttribute(self::ZOMBIE_SPAWN_REINFORCEMENTS, 0.0, 1.0, 0.0);
	}

	/**
	 * @param string $id
	 * @param float  $minValue
	 * @param float  $maxValue
	 * @param float  $defaultValue
	 * @param bool   $shouldSend
	 *
	 * @return Attribute
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function addAttribute(string $id, float $minValue, float $maxValue, float $defaultValue, bool $shouldSend = true) : Attribute{
		if($minValue > $maxValue or $defaultValue > $maxValue or $defaultValue < $minValue){
			throw new \InvalidArgumentException("Invalid ranges: min value: $minValue, max value: $maxValue, $defaultValue: $defaultValue");
		}

		return self::$attributes[$id] = new Attribute($id, $minValue, $maxValue, $defaultValue, $shouldSend);
	}

	/**
	 * @param string $id
	 *
	 * @return Attribute|null
	 */
	public static function getAttribute(string $id) : ?Attribute{
		return isset(self::$attributes[$id]) ? clone self::$attributes[$id] : null;
	}

	private function __construct(string $id, float $minValue, float $maxValue, float $defaultValue, bool $shouldSend = true){
		$this->id = $id;
		$this->minValue = $minValue;
		$this->maxValue = $maxValue;
		$this->defaultValue = $defaultValue;
		$this->shouldSend = $shouldSend;

		$this->currentValue = $this->defaultValue;
	}

	public function getMinValue() : float{
		return $this->minValue;
	}

	public function setMinValue(float $minValue){
		if($minValue > ($max = $this->getMaxValue())){
			throw new \InvalidArgumentException("Minimum $minValue is greater than the maximum $max");
		}

		if($this->minValue != $minValue){
			$this->desynchronized = true;
			$this->minValue = $minValue;
		}
		return $this;
	}

	public function getMaxValue() : float{
		return $this->maxValue;
	}

	public function setMaxValue(float $maxValue){
		if($maxValue < ($min = $this->getMinValue())){
			throw new \InvalidArgumentException("Maximum $maxValue is less than the minimum $min");
		}

		if($this->maxValue != $maxValue){
			$this->desynchronized = true;
			$this->maxValue = $maxValue;
		}
		return $this;
	}

	public function getDefaultValue() : float{
		return $this->defaultValue;
	}

	public function setDefaultValue(float $defaultValue){
		if($defaultValue > $this->getMaxValue() or $defaultValue < $this->getMinValue()){
			throw new \InvalidArgumentException("Default $defaultValue is outside the range " . $this->getMinValue() . " - " . $this->getMaxValue());
		}

		if($this->defaultValue !== $defaultValue){
			$this->desynchronized = true;
			$this->defaultValue = $defaultValue;
		}
		return $this;
	}

	public function resetToDefault() : void{
		$this->setValue($this->getDefaultValue(), true);
	}

	public function getValue() : float{
		return $this->currentValue;
	}

	/**
	 * @param float $value
	 * @param bool  $fit
	 * @param bool  $forceSend
	 *
	 * @return $this
	 */
	public function setValue(float $value, bool $fit = false, bool $forceSend = false){
		if($value > $this->getMaxValue() or $value < $this->getMinValue()){
			if(!$fit){
				throw new \InvalidArgumentException("Value $value is outside the range " . $this->getMinValue() . " - " . $this->getMaxValue());
			}
			$value = min(max($value, $this->getMinValue()), $this->getMaxValue());
		}

		if($this->currentValue != $value){
			$this->desynchronized = true;
			$this->currentValue = $value;
		}elseif($forceSend){
			$this->desynchronized = true;
		}

		return $this;
	}

	public function getId() : string{
		return $this->id;
	}

	public function isSyncable() : bool{
		return $this->shouldSend;
	}

	public function isDesynchronized() : bool{
		return $this->shouldSend and $this->desynchronized;
	}

	public function markSynchronized(bool $synced = true) : void{
		$this->desynchronized = !$synced;
	}
}
