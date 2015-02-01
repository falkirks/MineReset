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
        for ($x = $this->getA()->getX(); $x-16 <= $this->getB()->getX(); $x += 16){
            for ($z = $this->getA()->getZ(); $z-16 <= $this->getB()->getZ(); $z += 16) {
                //$this->getLevel()->getServer()->getLogger()->info(Level::chunkHash($x >> 4, $z >> 4));
                $chunk = $this->level->getChunk($x >> 4, $z >> 4, true);
                $chunkClass = get_class($chunk);
                $chunks[Level::chunkHash($x >> 4, $z >> 4)] = $chunk->toBinary();
            }
        }

        //var_dump($chunks);
        $resetTask = new MineResetTask($chunks, $this->a, $this->b, $this->data, $this->getLevel()->getId(), $this->base->getRegionBlocker()->blockZone($this->a, $this->b, $this->level), $chunkClass);
        $this->base->scheduleReset($resetTask);
    }
}