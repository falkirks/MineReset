<?php
namespace falkirks\minereset\listener;


use falkirks\minereset\Mine;
use falkirks\minereset\MineReset;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;

class RegionBlockerListener implements Listener {
    /** @var  MineReset */
    private $api;

    /**
     * RegionBlockerListener constructor.
     * @param MineReset $api
     */
    public function __construct(MineReset $api){
        $this->api = $api;
    }


    public function clearMine(string $mineName){
        /** @var Mine $mine */
        $mine = $this->getApi()->getMineManager()[$mineName];
        if($mine !== null){
            foreach ($this->getApi()->getServer()->getOnlinePlayers() as $player){
                if($mine->isPointInside($player->getPosition())){
                    $player->teleport($player->getLevel()->getSafeSpawn($player->getPosition()));
                    $player->sendMessage("You have teleported to escape a resetting mine.");
                }
            }
        }
    }

    /**
     * @priority HIGH
     *
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event){

        $mine = $this->getResettingMineAtPosition($event->getBlock());
        if($mine != null){
            $event->getPlayer()->sendMessage(TextFormat::RED . "A mine is currently resetting in this area. You may not place blocks." . TextFormat::RESET);
            $event->setCancelled();
        }
    }

    /**
     * @priority HIGH
     *
     * @param BlockBreakEvent $event
     */
    public function onBlockDestroy(BlockBreakEvent $event){

        $mine = $this->getResettingMineAtPosition($event->getBlock());
        if($mine != null){
            $event->getPlayer()->sendMessage(TextFormat::RED . "A mine is currently resetting in this area. You may not break blocks." . TextFormat::RESET);
            $event->setCancelled();
        }
    }

    private function getResettingMineAtPosition(Position $position){
        foreach ($this->getApi()->getMineManager() as $mine) {
            if($mine->isResetting() && $mine->isPointInside($position)){
                return $mine;
            }
        }
        return null;
    }

    /**
     * @return MineReset
     */
    public function getApi(): MineReset{
        return $this->api;
    }


}