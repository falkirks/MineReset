<?php
namespace falkirks\minereset;

use falkirks\minereset\task\ResetTask;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\PluginTask;

/**
 * Class Mine
 *
 * Programmer note: Mine objects have no state. They can be generated arbitrarily from serialized data.
 *
 * @package falkirks\minereset\mine
 */
class Mine extends PluginTask {
    private $pointA;
    private $pointB;
    private $level;
    private $data;
    private $name;
    private $isResetting;

    private $resetInterval;

    private $api;


    /**
     * Mine constructor.
     * @param MineManager $api
     * @param Vector3 $pointA
     * @param Vector3 $pointB
     * @param string $level
     * @param string $name
     * @param array $data
     * @param int $resetInterval
     */
    public function __construct(MineManager $api, Vector3 $pointA, Vector3 $pointB, $level, string $name, array $data = [], int $resetInterval = -1){
        parent::__construct($api->getApi());

        $this->pointA = $pointA;
        $this->pointB = $pointB;
        $this->level = $level;
        $this->data = $data;
        $this->name = $name;
        $this->resetInterval = $resetInterval;
        $this->api = $api;

        $this->isResetting = false;
        $this->register();

    }


    /**
     * INTERNAL USE ONLY
     */
    public function register(){
        if($this->getHandler() === null && $this->resetInterval > 0){
            $this->getApi()->getApi()->getServer()->getScheduler()->scheduleRepeatingTask($this, 20 * $this->resetInterval);
        }
    }

    /**
     * INTERNAL USE ONLY
     */
    public function destroy(){
        if($this->getHandler() !== null) {
            $this->getApi()->getApi()->getServer()->getScheduler()->cancelTask($this->getTaskId());
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

    /**
     * @param bool $force NOT TESTED
     * @return bool
     */
    public function reset($force = false){
        if((!$this->isResetting() || $force) && $this->getLevel() !== null){
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
            $this->getApi()->getApi()->getServer()->getScheduler()->scheduleAsyncTask($resetTask);
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
}
