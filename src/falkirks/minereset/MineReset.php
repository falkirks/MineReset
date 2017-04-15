<?php
namespace falkirks\minereset;


use pocketmine\plugin\PluginBase;

class MineReset extends PluginBase{

    public function onEnable(){
        @mkdir($this->getDataFolder());
    }

}