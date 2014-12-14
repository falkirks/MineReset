<?php

namespace minereset;



use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class LongReset extends PluginTask{
    public $mines;
    public function __construct(Plugin $base){
        parent::__construct($base);
        $this->mines = [];
    }
    public function onRun($t){
        if(isset($this->mines[0])){
            for ($i = $this->mines[0][0]->getX(); $i <= $this->mines[0][1]->getX(); $i++) {
                for ($j = $this->mines[0][0]->getY(); $j <= $this->mines[0][1]->getY(); $j++) {
                    for ($k = $this->mines[0][0]->getZ(); $k <= $this->mines[0][1]->getZ(); $k++) {
                        $a = rand(0, end($this->mines[0][3]));
                        for ($l = 0; $l < count($this->mines[0][3]); $l++) {
                            if ($a <= $this->mines[0][3][$l]) {
                                $this->mines[0][2]->setBlock(new Vector3($i, $j, $k), Block::get($this->mines[0][4][$l]));
                                break;
                            }
                        }
                    }
                    $this->mines[0][0] = $this->mines[0][0]->add(0,1);
                    return;
                }
                $this->mines[0][0] = $this->mines[0][0]->add(1);
                $this->mines[0][0]->y = $this->mines[0][5];
                return;
            }
            array_shift($this->mines);
        }
        else{
            $this->getOwner()->getServer()->getScheduler()->cancelTask($this->getTaskId());
        }
    }
    public function scheduleLongReset(Mine $mine){
        $id = array_keys($mine->getData());
        $m = array_values($mine->getData());
        $sum[0] = $m[0];
        for ($l = 1; $l < count($m); $l++) $sum[$l] = $sum[$l - 1] + $m[$l];
        $this->mines[] = [$mine->getA(), $mine->getB(), $mine->getLev(), $sum, $id, $mine->getA()->getY()];
        $this->getOwner()->getServer()->getScheduler()->scheduleRepeatingTask($this, 2);
    }
}