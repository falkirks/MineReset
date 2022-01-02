<?php
/**
 * Created by PhpStorm.
 * User: noahheyl
 * Date: 2018-02-01
 * Time: 10:56 AM
 */

namespace falkirks\minereset\util;


use falkirks\minereset\exception\InvalidBlockStringException;
use pocketmine\block\BlockLegacyIds;
use ReflectionClass;

class BlockStringParser
{
	private static array $blockMap = [];

	private static function ensureMap(): void {
		if (empty(self::$blockMap)) {
			self::$blockMap = (new ReflectionClass(BlockLegacyIds::class))->getConstants();
		}
	}

	public static function isValid(string $str): bool {
		self::ensureMap();
		if (is_numeric($str) || isset(self::$blockMap[strtoupper($str)])) {
			return true;
		}

		$arr = explode(":", $str);
		if (count($arr) === 2 && is_numeric($arr[1])) {
			return is_numeric($arr[0]) || isset(self::$blockMap[strtoupper($arr[0])]);
		}

		return isset(self::$blockMap[strtoupper($str)]);

	}

	/**
	 * @param string $str
	 * @return array
	 * @throws InvalidBlockStringException
	 */
	public static function parse(string $str): array {
		self::ensureMap();

		if (is_numeric($str)) {
			return [$str, 0];
		} elseif (isset(self::$blockMap[strtoupper($str)])) {
			return [self::$blockMap[strtoupper($str)], 0];
		}


		$arr = explode(":", $str);
		if (count($arr) === 2 && is_numeric($arr[1])) {
			if (is_numeric($arr[0])) {
				return [$arr[0], $arr[1]];
			} elseif (isset(self::$blockMap[strtoupper($arr[0])])) {
				return [self::$blockMap[strtoupper($arr[0])], $arr[1]];
			}
		}

		throw new InvalidBlockStringException();
	}

}