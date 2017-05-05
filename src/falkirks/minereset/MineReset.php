<?php
namespace falkirks\minereset;


use falkirks\minereset\command\AboutCommand;
use falkirks\minereset\command\CreateCommand;
use falkirks\minereset\command\DestroyCommand;
use falkirks\minereset\command\ListCommand;
use falkirks\minereset\command\MineCommand;
use falkirks\minereset\command\ResetAllCommand;
use falkirks\minereset\command\ResetCommand;
use falkirks\minereset\command\SetCommand;
use falkirks\minereset\listener\CreationListener;
use falkirks\minereset\listener\RegionBlockerListener;
use falkirks\minereset\store\EntityStore;
use falkirks\minereset\store\YAMLStore;
use falkirks\minereset\task\ScheduledResetTaskPool;
use pocketmine\level\Level;
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
    /** @var  RegionBlockerListener */
    private $regionBlockerListener;
    /** @var  MineCommand */
    private $mainCommand;
    /** @var  bool */
    private static $supportsChunkSetting = null;

    /** @var  CreationListener */
    private $creationListener;

    public function onEnable(){
        self::detectChunkSetting();

        @mkdir($this->getDataFolder());

        $this->mineManager = new MineManager($this, new YAMLStore(new Config($this->getDataFolder() . "mines.yml", Config::YAML, [])));

        $this->resetProgressManager = new ResetProgressManager($this);

        $this->regionBlockerListener = new RegionBlockerListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->regionBlockerListener, $this);

        $this->creationListener = new CreationListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->creationListener, $this);

        $this->mainCommand = new MineCommand($this);
        $this->getServer()->getCommandMap()->register("minereset", $this->mainCommand);

        $this->mainCommand->registerSubCommand("about", new AboutCommand($this), ['a']);
        $this->mainCommand->registerSubCommand("list", new ListCommand($this), ['l']);
        $this->mainCommand->registerSubCommand("create", new CreateCommand($this), ['c']);
        $this->mainCommand->registerSubCommand("set", new SetCommand($this), ['s']);
        $this->mainCommand->registerSubCommand("destroy", new DestroyCommand($this), ['d']);
        $this->mainCommand->registerSubCommand("reset", new ResetCommand($this), ['r']);
        $this->mainCommand->registerSubCommand("reset-all", new ResetAllCommand($this), ['ra']);

        if(!self::supportsChunkSetting()){
            $this->getLogger()->warning("Your server does not support setting chunks without unloading them. This will cause tiles and entities to be lost when resetting mines. Upgrade to a newer pmmp to resolve this.");
        }

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

    /**
     * @return RegionBlockerListener
     */
    public function getRegionBlockerListener(): RegionBlockerListener{
        return $this->regionBlockerListener;
    }


    public static function supportsChunkSetting(): bool {
        return static::$supportsChunkSetting;
    }

    private static function detectChunkSetting(){
        if(self::$supportsChunkSetting === null) {
            $class = new \ReflectionClass(Level::class);
            $func = $class->getMethod("setChunk");
            $filename = $func->getFileName();
            $start_line = $func->getStartLine() - 1;
            $end_line = $func->getEndLine();
            $length = $end_line - $start_line;

            $source = file($filename);
            $body = implode("", array_slice($source, $start_line, $length));
            self::$supportsChunkSetting = strpos($body, 'removeEntity') !== false;
        }
    }
}