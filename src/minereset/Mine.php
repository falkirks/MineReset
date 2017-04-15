<?php
namespace minereset;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class Mine{
    private $a;
    private $b;
    private $level;
    private $data;
    private $name;
    /** @var MineReset $base */
    private $base;
    public function __construct(MineReset $base, string $name, Vector3 $a, Vector3 $b, int $level, array $data = []){
        $this->a = $a;
        $this->b = $b;
        $this->base = $base;
        $this->name = $name;
        $this->data = $data;
        $this->level = $level;
    }
    /**
     * @return bool
     */
    public function isMineSet(){
        return (count($this->data) != 0);
    }
    /**
     * @param array $arr
     */
    public function setData(array $arr){
        $this->data = $arr;
    }
    /**
     * @return Vector3
     */
    public function getA(){
        return $this->a;
    }
    /**
     * @return Vector3
     */
    public function getB(){
        return $this->b;
    }
    /**
     * @return Level|null
     */
    public function getLevel(){
        return $this->base->getServer()->getLevel($this->level);
    }
    public function getName() {
        return $this->name;
    }
    /**
     * @return array
     */
    public function getData(){
        return $this->data;
    }
    public function resetMine(){
        $chunks = [];
        $chunkClass = Chunk::class;
        for ($x = $this->getA()->getX(); $x-16 <= $this->getB()->getX(); $x += 16){
            for ($z = $this->getA()->getZ(); $z-16 <= $this->getB()->getZ(); $z += 16) {
                $chunk = $this->getLevel()->getChunk($x >> 4, $z >> 4, true);
                $chunkClass = get_class($chunk);
                $chunks[Level::chunkHash($x >> 4, $z >> 4)] = $chunk->fastSerialize();
            }
        }
        $resetTask = new MineResetTask($chunks, $this->a, $this->b, $this->data, $this->level, $this->base->getRegionBlocker()->blockZone($this->a, $this->b, $this->getLevel()), $chunkClass);
        $this->base->getServer()->getScheduler()->scheduleAsyncTask($resetTask);
    }
    public function __toString(){
        return $this->getName();
    }
}
