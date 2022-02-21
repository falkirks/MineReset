<?php

namespace falkirks\minereset;

use falkirks\minereset\command\CreateCommand;
use falkirks\minereset\command\DestroyCommand;
use falkirks\minereset\command\EditCommand;
use falkirks\minereset\command\ListCommand;
use falkirks\minereset\command\MineCommand;
use falkirks\minereset\command\ReportCommand;
use falkirks\minereset\command\ResetAllCommand;
use falkirks\minereset\command\ResetCommand;
use falkirks\minereset\command\SetCommand;
use falkirks\minereset\listener\CreationListener;
use falkirks\minereset\listener\RegionBlockerListener;
use falkirks\minereset\store\ConfigStore;
use falkirks\minereset\util\DebugDumpFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\World;
use ReflectionClass;


/**
 * MineReset is a powerful mine resetting tool for PocketMine
 *
 * Class MineReset
 *
 * @package falkirks\minereset
 */
class MineReset extends PluginBase
{
    /** @var  bool */
    private static ?bool $supportsChunkSetting = null;
    /** @var  MineManager */
    private MineManager $mineManager;
    /** @var  ResetProgressManager */
    private ResetProgressManager $resetProgressManager;
    /** @var  RegionBlockerListener */
    private RegionBlockerListener $regionBlockerListener;
    /** @var  MineCommand */
    private MineCommand $mainCommand;
    /** @var DebugDumpFactory */
    private DebugDumpFactory $debugDumpFactory;
    /** @var  CreationListener */
    private CreationListener $creationListener;

    public function onEnable(): void
    {
        self::detectChunkSetting();

        $this->debugDumpFactory = new DebugDumpFactory($this);

        $this->mineManager = new MineManager($this, new ConfigStore(new Config($this->getDataFolder() . "mines.json", Config::JSON, [])));

        $this->resetProgressManager = new ResetProgressManager($this);

        $this->regionBlockerListener = new RegionBlockerListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->regionBlockerListener, $this);

        $this->creationListener = new CreationListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->creationListener, $this);

        $this->mainCommand = new MineCommand($this);
        $this->getServer()->getCommandMap()->register("minereset", $this->mainCommand);

        $this->mainCommand->registerSubCommand("report", new ReportCommand($this), []);
        $this->mainCommand->registerSubCommand("list", new ListCommand($this), ['l']);
        $this->mainCommand->registerSubCommand("create", new CreateCommand($this), ['c']);
        $this->mainCommand->registerSubCommand("set", new SetCommand($this), ['s']);
        $this->mainCommand->registerSubCommand("destroy", new DestroyCommand($this), ['d']);
        $this->mainCommand->registerSubCommand("reset", new ResetCommand($this), ['r']);
        $this->mainCommand->registerSubCommand("reset-all", new ResetAllCommand($this), ['ra']);
        $this->mainCommand->registerSubCommand("edit", new EditCommand($this), ['e']);

        if (!self::supportsChunkSetting()) {
            $this->getLogger()->warning("Your server does not support setting chunks without unloading them. This will cause tiles and entities to be lost when resetting mines. Upgrade to a newer pmmp to resolve this.");
        }

    }

    private static function detectChunkSetting(): void
    {
        if (self::$supportsChunkSetting === null) {
            $class = new ReflectionClass(World::class);
            $func = $class->getMethod("setChunk");
            $filename = $func->getFileName();
            $start_line = $func->getStartLine() - 1;
            $end_line = $func->getEndLine();
            $length = $end_line - $start_line;

            $source = file($filename);
            $body = implode("", array_slice($source, $start_line, $length));
            self::$supportsChunkSetting = str_contains($body, 'removeEntity');
        }
    }

    public static function supportsChunkSetting(): bool
    {
        return static::$supportsChunkSetting;
    }

    public function onDisable(): void
    {
        $this->mineManager->saveAll();
    }

    /**
     * @return MineManager
     */
    public function getMineManager(): MineManager
    {
        return $this->mineManager;
    }

    /**
     * @return ResetProgressManager
     */
    public function getResetProgressManager(): ResetProgressManager
    {
        return $this->resetProgressManager;
    }

    /**
     * @return MineCommand
     */
    public function getMainCommand(): MineCommand
    {
        return $this->mainCommand;
    }

    /**
     * @return CreationListener
     */
    public function getCreationListener(): CreationListener
    {
        return $this->creationListener;
    }

    /**
     * @return RegionBlockerListener
     */
    public function getRegionBlockerListener(): RegionBlockerListener
    {
        return $this->regionBlockerListener;
    }

    /**
     * @return DebugDumpFactory
     */
    public function getDebugDumpFactory(): DebugDumpFactory
    {
        return $this->debugDumpFactory;
    }
}