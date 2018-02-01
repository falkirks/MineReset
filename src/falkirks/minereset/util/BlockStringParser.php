<?php
/**
 * Created by PhpStorm.
 * User: noahheyl
 * Date: 2018-02-01
 * Time: 10:56 AM
 */

namespace falkirks\minereset\util;


use falkirks\minereset\exception\InvalidBlockStringException;

class BlockStringParser{

    public static function isValid(string $str): bool {
        if(is_numeric($str)){
            return true;
        }

        $arr = explode(":", $str);
        if(count($arr) === 2 && is_numeric($arr[0]) && is_numeric($arr[1])){
            return true;
        }

        return false;

    }

    /**
     * @param string $str
     * @return array
     * @throws InvalidBlockStringException
     */
    public static function parse(string $str): array{
        if (is_numeric($str)) {
            return [$str, 0];
        }

        $arr = explode(":", $str);
        if (count($arr) === 2 && is_numeric($arr[0]) && is_numeric($arr[1])) {
            return [$arr[0], $arr[1]];
        }

        throw new InvalidBlockStringException();
    }

}