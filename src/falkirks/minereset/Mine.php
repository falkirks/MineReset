<?php
namespace falkirks\minereset;

use falkirks\minereset\task\ResetTask;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
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
    private $isResetting;

    private $api;

    /**
     * Mine constructor.
     * @param MineManager $api
     * @param Vector3 $pointA
     * @param Vector3 $pointB
     * @param string $level
     * @param string $name
     * @param array $data
     */
    public function __construct(MineManager $api, Vector3 $pointA, Vector3 $pointB, $level, string $name, array $data = []){
        $this->pointA = $pointA;
        $this->pointB = $pointB;
        $this->level = $level;
        $this->data = $data;
        $this->name = $name;
        $this->api = $api;

        $this->isResetting = false;
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

    public function isPointInside(Position $position): bool{
        if($position->getLevel()->getId() !== $this->getLevel()->getId()){
            return false;
        }

        return $position->getX() >= $this->getPointA()->getX()
            && $position->getX() <= $this->getPointB()->getX()
            && $position->getY() >= $this->getPointA()->getY()
            && $position->getY() <= $this->getPointB()->getY()
            && $position->getZ() >= $this->getPointA()->getZ()
            && $position->getZ() <= $this->getPointB()->getZ();
    }

    /**
     * @return Level
     */
    public function getLevel(): Level{
        return $this->api->getApi()->getServer()->getLevelByName($this->level);
    }

    /**
     * @return string
     */
    public function getLevelName(): string {
        return $this->level;
    }

    /**
     * @return array
     */
    public function getData(): array{
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data){
        $this->data = $data;
        $this->getApi()->offsetSet($this->getName(), $this);
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return MineManager
     */
    public function getApi(): MineManager{
        return $this->api;
    }

    /**
     * @return bool
     */
    public function isResetting(){
        return $this->isResetting;
    }

    public function reset(){
        if(!$this->isResetting()){
            $this->isResetting = true;

            $chunks = [];
            $chunkClass = Chunk::class;
            for ($x = $this->getPointA()->getX(); $x-16 <= $this->getPointB()->getX(); $x += 16){
                for ($z = $this->getPointA()->getZ(); $z-16 <= $this->getPointB()->getZ(); $z += 16) {
                    $chunk = $this->getLevel()->getChunk($x >> 4, $z >> 4, true);
                    $chunkClass = get_class($chunk);
                    $chunks[Level::chunkHash($x >> 4, $z >> 4)] = $chunk->fastSerialize();
                }
            }

            $resetTask = new ResetTask($this->getName(), $chunks, $this->getPointA(), $this->getPointB(), $this->data, $this->getLevel()->getId(), $chunkClass);
            $this->getApi()->getApi()->getServer()->getScheduler()->scheduleAsyncTask($resetTask);
            return true;
        }
        return false;
    }

    public function doneReset(){
        $this->isResetting = false;
    }

}