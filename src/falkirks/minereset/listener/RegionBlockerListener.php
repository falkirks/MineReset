<?php

namespace falkirks\minereset\listener;

use falkirks\minereset\Mine;
use falkirks\minereset\MineReset;
use falkirks\simplewarp\SimpleWarp;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class RegionBlockerListener implements Listener
{
    /** @var  MineReset */
    private MineReset $api;

    /**
     * RegionBlockerListener constructor.
     *
     * @param MineReset $api
     */
    public function __construct(MineReset $api)
    {
        $this->api = $api;
    }

    public function clearMine(string $mineName): void
    {
        /** @var Mine $mine */
        $mine = $this->getApi()->getMineManager()[$mineName];
        if ($mine !== null) {
            foreach ($this->getApi()->getServer()->getOnlinePlayers() as $player) {
                if ($mine->isPointInside($player->getPosition())) {
                    $this->teleportPlayer($player, $mine);
                    $player->sendMessage("You have teleported to escape a resetting mine.");
                }
            }
        }
    }

    /**
     * @return MineReset
     */
    public function getApi(): MineReset
    {
        return $this->api;
    }


    /** @noinspection NotOptimalIfConditionsInspection */
    /** @noinspection PhpUndefinedClassInspection */
    public function teleportPlayer(Player $player, Mine $mine): void
    {
        $swarp = $this->getApi()->getServer()->getPluginManager()->getPlugin('SimpleWarp');
        if ($mine->hasWarp() && $swarp instanceof SimpleWarp) {
            $swarp->getApi()->warpPlayerTo($player, $mine->getWarpName());
        } else {
            $player->teleport($player->getWorld()->getSafeSpawn($player->getPosition()));
        }
    }

    /**
     * @priority HIGH
     *
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {

        $mine = $this->getResettingMineAtPosition($event->getBlock()->getPosition());
        if ($mine !== null) {
            $event->getPlayer()->sendMessage(TextFormat::RED . "A mine is currently resetting in this area. You may not place blocks." . TextFormat::RESET);
            $event->cancel();
        }
    }

    private function getResettingMineAtPosition(Position $position)
    {
        foreach ($this->getApi()->getMineManager() as $mine) {
            if ($mine->isResetting() && $mine->isPointInside($position)) {
                return $mine;
            }
        }
        return null;
    }

    /**
     * @priority HIGH
     *
     * @param BlockBreakEvent $event
     */
    public function onBlockDestroy(BlockBreakEvent $event): void
    {

        $mine = $this->getResettingMineAtPosition($event->getBlock()->getPosition());
        if ($mine !== null) {
            $event->getPlayer()->sendMessage(TextFormat::RED . "A mine is currently resetting in this area. You may not break blocks." . TextFormat::RESET);
            $event->cancel();
        }
    }


}