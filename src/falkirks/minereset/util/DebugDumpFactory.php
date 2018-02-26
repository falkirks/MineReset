<?php

namespace falkirks\minereset\util;


use falkirks\minereset\Mine;
use falkirks\minereset\MineReset;

class DebugDumpFactory{
    /** @var  MineReset */
    private $api;


    /**
     * DebugDump constructor.
     */
    public function __construct(MineReset $mineReset){
        $this->api = $mineReset;
    }


    public function generate(): string {
        return implode("\n", [
            "SERVER VERSION: " . $this->getApi()->getServer()->getPocketMineVersion(),
            "API: " . $this->getApi()->getServer()->getApiVersion(),
            "MCPE VERSION: " . $this->getApi()->getServer()->getVersion(),
            "SOFTWARE: " . $this->getApi()->getServer()->getName(),
            "MineReset Version: " . $this->getApi()->getDescription()->getVersion(),
            "PLUGINS: " . implode(",", array_keys($this->getApi()->getServer()->getPluginManager()->getPlugins())),
            "storage-mode: " . $this->getApi()->getMineManager()->getFlag(),
            json_encode($this->getApi()->getMineManager()->getMines(), JSON_PRETTY_PRINT)
        ]);
    }


    function __toString(){
        return $this->generate();
    }

    /**
     * @return MineReset
     */
    public function getApi(): MineReset{
        return $this->api;
    }
}