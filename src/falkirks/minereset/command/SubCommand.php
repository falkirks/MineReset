<?php

namespace falkirks\minereset\command;

use falkirks\minereset\MineReset;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;

abstract class SubCommand
{
    /** @var  MineReset */
    private MineReset $api;

    /**
     * SubCommand constructor.
     *
     * @param MineReset $api
     */
    public function __construct(MineReset $api)
    {
        $this->api = $api;
    }


    abstract public function execute(CommandSender $sender, $commandLabel, array $args);

    public function formsSupported(): bool
    {
        return $this->getApi()->getServer()->getPluginManager()->getPlugin("EasyForms") instanceof Plugin;
    }

    /**
     * @return MineReset
     */
    public function getApi(): MineReset
    {
        return $this->api;
    }
}