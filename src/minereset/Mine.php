<?php
namespace minereset;

use pocketmine\block\Block;
use pocketmine\level\format\mcregion\Chunk;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class Mine{
    public $a, $b, $lev, $data;
    /** @var MineReset  */
    private $base;
    public function __construct(MineReset $base, Vector3 $a, Vector3 $b, Level $level, array $data = []){
        $this->a = $a;
        $this->b = $b;
        $this->base = $base;
        $this->data = $data;
        $this->level = $level;
    }
    public function isMineSet(){
        return (count($this->data) != 0);
    }
    public function setData(array $arr){
        $this->data = $arr;
    }
    public function getA(){
        return $this->a;
    }
    public function getB(){
        return $this->b;
    }
    public function getLevel(){
        return $this->level;
    }
    public function getData(){
        return $this->data;
    }
    public function resetMine(){
        $chunks = [];
        for ($x = $this->getA()->getX() >> 4; $x <= $this->getB()->getX() >> 4; $x ++){
            for ($z = $this->getA()->getZ() >> 4; $z <= $this->getB()->getZ() >> 4; $z ++) {
                $chunk = $this->level->getChunk($x, $z, true);
                $chunkClass = get_class($chunk);
                $chunks[Level::chunkHash($x, $z)] = $chunk->toBinary();
            }
        }
        $resetTask = new MineResetTask($chunks, $this->a, $this->b, $this->data, $this->getLevel()->getId(), $this->base->getRegionBlocker()->blockZone($this->a, $this->b, $this->level), $chunkClass);
        $this->base->scheduleReset($resetTask);
    }
}