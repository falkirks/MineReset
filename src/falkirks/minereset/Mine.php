<?php
namespace falkirks\minereset;

use falkirks\minereset\exception\JsonFieldMissingException;
use falkirks\minereset\task\ResetTask;
use falkirks\minereset\util\BlockStringParser;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

/**
 * Class Mine
 *
 * Programmer note: Mine objects have no state. They can be generated arbitrarily from serialized data.
 *
 * @package falkirks\minereset\mine
 */
class Mine extends Task implements \JsonSerializable {
    private $pointA;
    private $pointB;
    private $level;
    private $data;
    private $name;
    private $isResetting;


    private $resetInterval;
    private $warpName;

    private $api;


    /**
     * Mine constructor.
     * @param MineManager $api
     * @param Vector3 $pointA
     * @param Vector3 $pointB
     * @param string $levelName
     * @param string $name
     * @param array $data
     * @param int $resetInterval
     * @param string $warpName
     */
    public function __construct(MineManager $api,
                                Vector3 $pointA,
                                Vector3 $pointB,
                                string $levelName,
                                string $name,
                                array $data = [],
                                int $resetInterval = -1,
                                string $warpName = ""){
        $this->pointA = $pointA;
        $this->pointB = $pointB;
        $this->level = $levelName;
        $this->data = $data;
        $this->name = $name;
        $this->resetInterval = $resetInterval;
        $this->warpName = $warpName;
        $this->api = $api;

        $this->isResetting = false;


        if($this->isValid()) {
            $this->register();
        }
        else{
            $api->getApi()->getLogger()->warning("MineReset has detected corruption of the mines.yml file in mine with name {$this->name}, MineReset will not reset this mine.");
        }

    }

    public function isValid() : bool {
        foreach ($this->data as $id => $percent){
            if(!BlockStringParser::isValid($id) || !is_numeric($percent)){
                return false;
            }
        }
        return true;
    }


    /**
     * INTERNAL USE ONLY
     */
    public function register(){
        if($this->getHandler() === null && $this->resetInterval > 0){
            $this->getApi()->getApi()->getScheduler()->scheduleRepeatingTask($this, 20 * $this->resetInterval);
        }
    }

    /**
     * INTERNAL USE ONLY
     */
    public function destroy(){
        if($this->getHandler() !== null) {
            $this->getApi()->getApi()->getScheduler()->cancelTask($this->getTaskId());
        }
    }

    public function onRun(int $currentTick){
        $this->reset();
    }

    /**
     * @return Vector3
     */
    public function getPointA(): Vector3{
        return $this->pointA;
    }

    /**
     * @return Vector3
     */
    public function getPointB(): Vector3{
        return $this->pointB;
    }

    public function isPointInside(Position $position): bool{
        if($this->getLevel() !== null && $position->getLevel()->getId() !== $this->getLevel()->getId()){
            return false;
        }

        return $position->getX() >= $this->getPointA()->getX()
            && $position->getX() <= $this->getPointB()->getX()
            && $position->getY() >= $this->getPointA()->getY()
            && $position->getY() <= $this->getPointB()->getY()
            && $position->getZ() >= $this->getPointA()->getZ()
            && $position->getZ() <= $this->getPointB()->getZ();
    }

    /**
     * @return Level | null
     */
    public function getLevel(){
        return $this->api->getApi()->getServer()->getLevelByName($this->level);
    }

    /**
     * @return string
     */
    public function getLevelName(): string {
        return $this->level;
    }

    /**
     * @return array
     */
    public function getData(): array{
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data){
        $this->data = $data;
        $this->getApi()->offsetSet($this->getName(), $this);
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return MineManager
     */
    public function getApi(): MineManager{
        return $this->api;
    }

    /**
     * @return bool
     */
    public function isResetting(){
        return $this->isResetting;
    }

    public function hasWarp(){
        return $this->warpName !== "";
    }

    public function getWarpName(){
        return $this->warpName;
    }

    /**
     * @param bool $force NOT TESTED
     * @return bool
     */
    public function reset($force = false){
        if((!$this->isResetting() || $force) && $this->getLevel() !== null && $this->isValid()){
            $this->isResetting = true;

            $chunks = [];
            $chunkClass = Chunk::class;
            for ($x = $this->getPointA()->getX(); $x-16 <= $this->getPointB()->getX(); $x += 16){
                for ($z = $this->getPointA()->getZ(); $z-16 <= $this->getPointB()->getZ(); $z += 16) {
                    $chunk = $this->getLevel()->getChunk($x >> 4, $z >> 4, true);

                    $chunkClass = get_class($chunk);
                    $chunks[Level::chunkHash($x >> 4, $z >> 4)] = $chunk->fastSerialize();
                }
            }

            $resetTask = new ResetTask($this->getName(), $chunks, $this->getPointA(), $this->getPointB(), $this->data, $this->getLevel()->getId(), $chunkClass);
            $this->getApi()->getApi()->getServer()->getAsyncPool()->submitTask($resetTask);
            return true;
        }
        return false;
    }

    /**
     * @return int
     */
    public function getResetInterval(): int{
        return $this->resetInterval;
    }

    /**
     * @param int $resetInterval
     */
    public function setResetInterval(int $resetInterval){
        $this->resetInterval = $resetInterval;
        $this->destroy();
        $this->register();
    }

    public function doneReset(){
        $this->isResetting = false;
    }

    public function __toString(){
        return $this->name;
    }

    public function jsonSerialize(){
        return [
            'name' => $this->name,
            'pointA' => [$this->pointA->getX(), $this->pointA->getY(), $this->pointA->getZ()],
            'pointB' => [$this->pointB->getX(), $this->pointB->getY(), $this->pointB->getZ()],
            'level' => $this->level,
            'data' => $this->data,
            'resetInterval' => $this->resetInterval,
            'warpName' => $this->warpName
        ];
    }

    /**
     * @param MineManager $manager
     * @param $json
     * @param $name
     * @return Mine
     * @throws JsonFieldMissingException
     */
    public static function fromJson(MineManager $manager, $json, $name): Mine{
        if(isset($json['pointA']) && isset($json['pointB']) && isset($json['level']) && isset($json['data'])){
            $a = new Mine($manager,
                new Vector3(...$json['pointA']),
                new Vector3(...$json['pointB']),
                $json['level'],
                $name,
                $json['data'],
                $json['resetInterval'] ?? -1,
                $json['warpName'] ?? "");
            return $a;
        }
        throw new JsonFieldMissingException();
    }
}
