<?php
namespace minereset;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class Mine{
    public $base, $a, $b, $lev, $data;
    public function __construct(MineReset $base, Vector3 $a, Vector3 $b, Level $lev, array $data = []){
        $this->a = $a;
        $this->b = $b;
        $this->base = $base;
        $this->data = $data;
        $this->lev = $lev;
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
    public function getLev(){
        return $this->lev;
    }
    public function getData(){
        return $this->data;
    }
    public function resetMine(){
        $sum = [];
        $id = array_keys($this->getData());
        $m = array_values($this->getData());
        $sum[0] = $m[0];
        for ($l = 1; $l < count($m); $l++) $sum[$l] = $sum[$l - 1] + $m[$l];
        foreach ($this->base->getServer()->getOnlinePlayers() as $p) {
            if ($p->getX() >= $this->getA()->getX() && $p->getX() <= $this->getB()->getX() && $p->getY() >= $this->getA()->getY() && $p->getY() <= $this->getB()->getY() && $p->getZ() >= $this->getA()->getZ() && $p->getZ() <= $this->getB()->getZ()){
                $p->teleport($this->getLev()->getSpawnLocation());
                $p->sendMessage("You have been saved from suffocation.");
            }
        }
        $send = (($this->getB()->getX()-$this->getA()->getX())*($this->getB()->getY()-$this->getA()->getY())*($this->getB()->getZ()-$this->getA()->getZ()) < 524288);
        for ($i = $this->getA()->getX(); $i <= $this->getB()->getX(); $i++) {
            for ($j = $this->getA()->getY(); $j <= $this->getB()->getY(); $j++) {
                for ($k = $this->getA()->getZ(); $k <= $this->getB()->getZ(); $k++) {
                    $a = rand(0, end($sum));
                    for ($l = 0; $l < count($sum); $l++) {
                        if ($a <= $sum[$l]) {
                            $this->getLev()->setBlock(new Vector3($i, $j, $k), Block::get($id[$l], 0), $send, false);
                            $l = count($sum);
                        }
                    }
                }
            }
        }
        /*
            Code from WorldEditor by @shoghicp
        */
        if($send === false){
            $forceSend = function($X, $Y, $Z){
                $this->changedCount[$X.":".$Y.":".$Z] = 4096;
            };
            $forceSend->bindTo($this->getLev(), $this->getLev());
            for($X = $this->getA()->getX() >> 4; $X <= ($this->getB()->getX() >> 4); ++$X){
                for($Y = $this->getA()->getY() >> 4; $Y <= ($this->getB()->getY() >> 4); ++$Y){
                    for($Z = $this->getA()->getZ() >> 4; $Z <= ($this->getB()->getZ() >> 4); ++$Z){
                        $forceSend($X,$Y,$Z);
                    }
                }
            }
        }
        /*

        */
    }
    public function longReset(){
        $this->base->longReset->scheduleLongReset($this);
    }

}