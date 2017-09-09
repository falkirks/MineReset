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
        /** @var Chunk[] $chunks */
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

        //Get these as local variables so they don't keep getting serialized/unserialized every single access
        $posA = $this->a;
        $posB = $this->b;

        $totalBlocks = ($posB->x - $posA->x + 1)*($posB->y - $posA->y + 1)*($posB->z - $posA->z + 1);
        $interval = $totalBlocks / 8; //TODO determine the interval programmatically
        $lastUpdate = 0;
        $currentBlocks = 0;

        $currentChunkX = $posA->x >> 4;
        $currentChunkZ = $posA->z >> 4;

        $currentChunkY = $posA->y >> 4;

        $currentChunk = null;
        $currentSubChunk = null;

        for ($x = $posA->getX(), $x2 = $posB->getX(); $x <= $x2; $x++) {
            $chunkX = $x >> 4;
            for ($z = $posA->getZ(), $z2 = $posB->getZ(); $z <= $z2; $z++) {
                $chunkZ = $z >> 4;
                if($currentChunk === null or $chunkX !== $currentChunkX or $chunkZ !== $currentChunkZ){
                    $currentChunkX = $chunkX;
                    $currentChunkZ = $chunkZ;

                    $hash = Level::chunkHash($chunkX, $chunkZ);
                    $currentChunk = $chunks[$hash];
                    if($currentChunk === null){
                        continue;
                    }
                }

                for ($y = $posA->getY(), $y2 = $posB->getY(); $y <= $y2; $y++) {
                    $chunkY = $y >> 4;

                    if($currentSubChunk === null or $chunkY !== $currentChunkY){
                        $currentChunkY = $chunkY;

                        $currentSubChunk = $currentChunk->getSubChunk($chunkY, true);
                        if($currentSubChunk === null){
                            continue;
                        }
                    }

                    $a = rand(0, end($sum));
                    for ($l = 0; $l < $sumCount; $l++) {
                        if ($a <= $sum[$l]) {
                            $currentSubChunk->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $id[$l][0] & 0xff, $id[$l][1] & 0xff);
                            $currentBlocks++;
                            if($lastUpdate + $interval <= $currentBlocks){
                                if(method_exists($this, 'publishProgress')) {
                                    $this->publishProgress(round(($currentBlocks / $totalBlocks) * 100) . "%");
                                }
                                $lastUpdate = $currentBlocks;
                            }

                            break;
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