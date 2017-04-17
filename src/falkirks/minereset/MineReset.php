<?php
namespace falkirks\minereset;


use falkirks\minereset\command\CreateCommand;
use falkirks\minereset\command\DestroyCommand;
use falkirks\minereset\command\ListCommand;
use falkirks\minereset\command\MineCommand;
use falkirks\minereset\command\ResetAllCommand;
use falkirks\minereset\command\ResetCommand;
use falkirks\minereset\command\SetCommand;
use falkirks\minereset\listener\CreationListener;
use falkirks\minereset\store\YAMLStore;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;


/**
 * MineReset is a powerful mine resetting tool for PocketMine
 *
 * Class MineReset
 * @package falkirks\minereset
 */
class MineReset extends PluginBase{

    /** @var  MineManager */
    private $mineManager;
    /** @var  ResetProgressManager */
    private $resetProgressManager;
    /** @var  MineCommand */
    private $mainCommand;

    /** @var  CreationListener */
    private $creationListener;

    public function onEnable(){
        @mkdir($this->getDataFolder());

        $this->mineManager = new MineManager($this, new YAMLStore(new Config($this->getDataFolder() . "mines.yml", Config::YAML, [])));

        $this->resetProgressManager = new ResetProgressManager($this);

        $this->creationListener = new CreationListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->creationListener, $this);

        $this->mainCommand = new MineCommand($this);
        $this->getServer()->getCommandMap()->register("minereset", $this->mainCommand);

        $this->mainCommand->registerSubCommand("list", new ListCommand($this));
        $this->mainCommand->registerSubCommand("create", new CreateCommand($this));
        $this->mainCommand->registerSubCommand("set", new SetCommand($this));
        $this->mainCommand->registerSubCommand("destroy", new DestroyCommand($this));
        $this->mainCommand->registerSubCommand("create", new CreateCommand($this));
        $this->mainCommand->registerSubCommand("reset", new ResetCommand($this));
        $this->mainCommand->registerSubCommand("reset-all", new ResetAllCommand($this));
    }

    public function onDisable(){
        $this->mineManager->saveAll();
    }

    /**
     * @return MineManager
     */
    public function getMineManager(): MineManager{
        return $this->mineManager;
    }

    /**
     * @return ResetProgressManager
     */
    public function getResetProgressManager(): ResetProgressManager{
        return $this->resetProgressManager;
    }

    /**
     * @return MineCommand
     */
    public function getMainCommand(): MineCommand{
        return $this->mainCommand;
    }

    /**
     * @return CreationListener
     */
    public function getCreationListener(): CreationListener{
        return $this->creationListener;
    }


}