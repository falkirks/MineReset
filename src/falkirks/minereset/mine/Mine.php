<?php
namespace falkirks\minereset\mine;


use falkirks\minereset\MineReset;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class Mine
 * @package falkirks\minereset\mine
 */
class Mine{
    private $pointA;
    private $pointB;
    private $level;
    private $data;
    private $name;

    private $api;

    /**
     * Mine constructor.
     * @param MineReset $api
     * @param Vector3 $pointA
     * @param Vector3 $pointB
     * @param Level $level
     * @param string $name
     * @param array $data
     */
    public function __construct(MineReset $api, Vector3 $pointA, Vector3 $pointB, Level $level, string $name, array $data = []){
        $this->pointA = $pointA;
        $this->pointB = $pointB;
        $this->level = $level;
        $this->data = $data;
        $this->name = $name;
        $this->api = $api;
    }

    /**
     * @return Vector3
     */
    public function getPointA(): Vector3{
        return $this->pointA;
    }

    /**
     * @return Vector3
     */
    public function getPointB(): Vector3{
        return $this->pointB;
    }

    /**
     * @return Level
     */
    public function getLevel(): Level{
        return $this->level;
    }

    /**
     * @return array
     */
    public function getData(): array{
        return $this->data;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return MineReset
     */
    public function getApi(): MineReset{
        return $this->api;
    }

}