<?php
namespace falkirks\minereset\task;

use falkirks\minereset\MineReset;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;


class ResetTask extends AsyncTask{
    /** @var  string */
    private $name;
    /** @var string $chunks */
    private $chunks;
    /** @var Vector3 $a */
    private $a;
    /** @var Vector3 $b */
    private $b;
    /** @var string $ratioData */
    private $ratioData;
    /** @var int $levelId */
    private $levelId;
    /** @var Chunk $chunkClass */
    private $chunkClass;

    public function __construct(string $name, array $chunks, Vector3 $a, Vector3 $b, array $data, $levelId, $chunkClass){
        $this->name = $name;
        $this->chunks = serialize($chunks);
        $this->a = $a;
        $this->b = $b;
        $this->ratioData = serialize($data);
        $this->levelId = $levelId;
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
            $chunks[$hash] = $chunkClass::fastDeserialize($binary);
        }
        $sum = [];
        $id = array_keys(unserialize($this->ratioData));

        foreach($id as $i => $blockId){
            $blockId = explode(":", $id[$i]);
            if(!isset($blockId[1])){
                $blockId[1] = 0;
            }
            $id[$i] = $blockId;
        }
        $m = array_values(unserialize($this->ratioData));
        $sum[0] = $m[0];
        for ($l = 1, $mCount = count($m); $l < $mCount; $l++)
            $sum[$l] = $sum[$l - 1] + $m[$l];

        $sumCount = count($sum);

        $totalBlocks = ($this->b->x - $this->a->x + 1)*($this->b->y - $this->a->y + 1)*($this->b->z - $this->a->z + 1);
        $interval = $totalBlocks / 8; //TODO determine the interval programmatically
        $lastUpdate = 0;
        $currentBlocks = 0;

        for ($x = $this->a->getX(), $x2 = $this->b->getX(); $x <= $x2; $x++) {
            for ($y = $this->a->getY(), $y2 = $this->b->getY(); $y <= $y2; $y++) {
                for ($z = $this->a->getZ(), $z2 = $this->b->getZ(); $z <= $z2; $z++) {
                    $a = rand(0, end($sum));
                    for ($l = 0; $l < $sumCount; $l++) {
                        if ($a <= $sum[$l]) {
                            $hash = Level::chunkHash($x >> 4, $z >> 4);
                            if(isset($chunks[$hash])){
                                $chunks[$hash]->setBlock($x & 0x0f, $y & 0x7f, $z & 0x0f, $id[$l][0] & 0xff, $id[$l][1] & 0xff);
                                $currentBlocks++;
                                if($lastUpdate + $interval <= $currentBlocks){
                                    if(method_exists($this, 'publishProgress')) {
                                        $this->publishProgress(round(($currentBlocks / $totalBlocks) * 100) . "%");
                                    }
                                    $lastUpdate = $currentBlocks;
                                }

                            }
                            $l = $sumCount;
                        }
                    }
                }
            }
        }
        $this->setResult($chunks);
    }
    /**
     * @param Server $server
     */
    public function onCompletion(Server $server){
        $chunks = $this->getResult();
        $plugin = $server->getPluginManager()->getPlugin("MineReset");
        if($plugin instanceof MineReset and $plugin->isEnabled()) {
            $level = $server->getLevel($this->levelId);
            if ($level instanceof Level) {
                foreach ($chunks as $hash => $chunk) {
                    Level::getXZ($hash, $x, $z);
                    $level->setChunk($x, $z, $chunk, !MineReset::supportsChunkSetting());

                }
            }
            $plugin->getRegionBlockerListener()->clearMine($this->name);
            $plugin->getResetProgressManager()->notifyComplete($this->name);
        }
    }

    /**
     * @param Server $server
     * @param mixed $progress
     */
    public function onProgressUpdate(Server $server, $progress){
        $plugin = $server->getPluginManager()->getPlugin("MineReset");
        if($plugin instanceof MineReset and $plugin->isEnabled()) {
            $plugin->getResetProgressManager()->notifyProgress($progress, $this->name);
        }
    }
}