<?php
namespace minereset;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class MineResetTask extends AsyncTask{
    private $chunks;
    private $a;
    private $b;
    private $ratioData;
    private $regionId;
    private $levelId;
    private $chunkClass;

    public function __construct(array $chunks, Vector3 $a, Vector3 $b, array $data, $levelId, $regionId, $chunkClass){
        parent::__construct(null);
        $this->chunks = serialize($chunks);
        $this->a = $a;
        $this->b = $b;
        $this->ratioData = serialize($data);
        $this->levelId = $levelId;
        $this->regionId = $regionId;
        $this->chunkClass = $chunkClass;
    }
    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun(){

        $chunkClass = $this->chunkClass;
        /** @var  Chunk[] $chunks */
        $chunks = unserialize($this->chunks);
        foreach($chunks as $hash => $binary){
            $chunks[$hash] = $chunkClass::fromBinary($binary);
        }
        $sum = [];
        $id = array_keys(unserialize($this->ratioData));
        for($i = 0; $i < count($id); $i++){
            $blockId = explode(":", $id[$i]);
            if(!isset($blockId[1])){
                $blockId[1] = 0;
            }
            $id[$i] = $blockId;
        }

        $m = array_values(unserialize($this->ratioData));
        $sum[0] = $m[0];
        for ($l = 1; $l < count($m); $l++) $sum[$l] = $sum[$l - 1] + $m[$l];

        for ($x = $this->a->getX(); $x <= $this->b->getX(); $x++) {
            for ($y = $this->a->getY(); $y <= $this->b->getY(); $y++) {
                for ($z = $this->a->getZ(); $z <= $this->b->getZ(); $z++) {
                    $a = rand(0, end($sum));
                    for ($l = 0; $l < count($sum); $l++) {
                        if ($a <= $sum[$l]) {
                            $hash = Level::chunkHash($x >> 4, $z >> 4);
                            if(isset($chunks[$hash])){
                                $chunks[$hash]->setBlock($x & 0x0f, $y & 0x7f, $z & 0x0f, $id[$l][0] & 0xff, $id[$l][1] & 0xff);
                            }
                            $l = count($sum);
                        }
                    }
                }
            }
        }
        $this->setResult($chunks);
    }
    public function onCompletion(Server $server){
        $chunks = $this->getResult();
        $plugin = $server->getPluginManager()->getPlugin("MineReset");
        if($plugin instanceof MineReset and $plugin->isEnabled()) {
            $level = $server->getLevel($this->levelId);
            if ($level != null) {
                foreach ($chunks as $hash => $chunk) {
                    Level::getXZ($hash, $x, $z);
                    $level->setChunk($x, $z, $chunk);
                }
            }
            $plugin->getRegionBlocker()->freeZone($this->regionId, $this->levelId);
        }
    }
}