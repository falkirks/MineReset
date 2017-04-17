<?php

namespace falkirks\minereset\listener;


use falkirks\minereset\Mine;
use falkirks\minereset\MineManager;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class MineCreationSession{
    /** @var  string */
    private $name;
    /** @var  Player */
    private $player;
    /** @var Vector3 */
    private $pointA;
    /** @var Vector3 */
    private $pointB;
    /** @var  Level */
    private $level;

    /**
     * MineCreationSession constructor.
     * @param string $name
     * @param Player $player
     */
    public function __construct(string $name, Player $player){
        $this->name = $name;
        $this->player = $player;
        $this->pointA = null;
        $this->pointB = null;
        $this->level = null;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player{
        return $this->player;
    }

    /**
     * @param Player $player
     */
    public function setPlayer(Player $player){
        $this->player = $player;
    }

    /**
     * @return Vector3 | null
     */
    public function getPointA(){
        return $this->pointA;
    }

    /**
     * @param Vector3 $pointA
     */
    public function setPointA(Vector3 $pointA){
        $this->pointA = $pointA;
    }

    /**
     * @return Vector3 | null
     */
    public function getPointB(){
        return $this->pointB;
    }

    /**
     * @param Vector3 $pointB
     */
    public function setPointB(Vector3 $pointB){
        $this->pointB = $pointB;
    }

    /**
     * @return Level | null
     */
    public function getLevel(){
        return $this->level;
    }

    /**
     * @param Level $level
     */
    public function setLevel(Level $level){
        $this->level = $level;
    }

    public function setNextPoint(Vector3 $point){
        if($this->pointA === null){
            $this->setPointA($point);
        }
        else if($this->pointB === null){
            $this->setPointB($point);
        }
    }

    public function canGenerate() : bool {
        return $this->pointA !== null && $this->pointB !== null && $this->level !== null;
    }

    public function generate(MineManager $owner): Mine{
        if($this->canGenerate()){
            $mine = new Mine($owner,
                new Vector3(min($this->pointA->getFloorX(), $this->pointB->getFloorX()), min($this->pointA->getFloorY(), $this->pointB->getFloorY()), min($this->pointA->getFloorZ(), $this->pointB->getFloorZ())),
                new Vector3(max($this->pointA->getFloorX(), $this->pointB->getFloorX()), max($this->pointA->getFloorY(), $this->pointB->getFloorY()), max($this->pointA->getFloorZ(), $this->pointB->getFloorZ())),
                $this->level->getName(),
                $this->name);
            $owner[$this->name] = $mine;
            return $mine;
        }
        else{
            throw new \InvalidStateException();
        }
    }

}