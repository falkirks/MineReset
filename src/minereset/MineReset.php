<?php
namespace minereset;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class MineReset extends PluginBase implements CommandExecutor, Listener{
    public $sessions;
    /** @var  Config */
    public $mineData;
    /** @var  Mine[] */
    public $mines;
    /** @var  RegionBlocker */
    private $regionBlocker;
    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->mineData = new Config($this->getDataFolder() . "mines.yml", Config::YAML, []);
        $this->mines = [];
        $this->parseMines();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->regionBlocker = new RegionBlocker($this);
        $this->sessions = [];
    }
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        if(isset($args[0])){
            if(!$sender->hasPermission("minereset.commmand." . $args[0])){
                $sender->sendMessage(TextFormat::RED . "You do not have permission." . TextFormat::RESET);
                return true;
            }
            else{
                switch($args[0]){
                    case "create":
                    case "c":
                        if($sender instanceof Player){
                            if(isset($args[1])){
                                if(!isset($this->mines[$args[1]])){
                                    $this->sessions[$sender->getName()] = [$args[1]];
                                    $sender->sendMessage("Tap a block to set as first position...");
                                    return true;
                                }
                                else{
                                    $sender->sendMessage(TextFormat::RED . "That mine already exists." . TextFormat::RESET);
                                    return true;
                                }
                            }
                            else{
                                $sender->sendMessage(TextFormat::RED . "You must specify a name." . TextFormat::RESET);
                                return true;
                            }
                        }
                        else{
                            $sender->sendMessage(TextFormat::RED . "Please run command in game." . TextFormat::RESET);
                            return true;
                        }
                        break;
                    case "destroy":
                    case "d":
                        if(isset($args[1])){
                            if(isset($this->mines[$args[1]])){
                                unset($this->mines[$args[1]]);
                                $this->saveConfig();
                                $sender->sendMessage("Mine " . $args[1] . " has been destroyed.");
                                return true;
                            }
                            else{
                                $sender->sendMessage(TextFormat::RED . "That mine doesn't exist." . TextFormat::RESET);
                                return true;
                            }
                        }
                        else{
                            $sender->sendMessage(TextFormat::RED . "You must specify a name." . TextFormat::RESET);
                            return true;
                        }
                        break;
                    case "set":
                    case "s":
                        if(isset($args[1])){
                            if(isset($args[3])){
                                if (isset($this->mines[$args[1]])) {
                                    $sets = array_slice($args, 2);
                                    $save = [];
                                    if(count($sets) % 2 === 0) {
                                        foreach ($sets as $key => $item) {
                                            if ($key & 1) {
                                                if (isset($save[$sets[$key - 1]])) {
                                                    $save[$sets[$key - 1]] += $item;
                                                } else {
                                                    $save[$sets[$key - 1]] = $item;
                                                }
                                            }
                                        }
                                        $this->mines[$args[1]]->setData($save);
                                        $sender->sendMessage("Mine setted.");
                                        $this->saveConfig();
                                        return true;
                                    }
                                    else{
                                        $sender->sendMessage(TextFormat::RED . "Your format string looks corrupted." . TextFormat::RESET);
                                        return true;
                                    }
                                }
                                else{
                                    $sender->sendMessage(TextFormat::RED . "Mine doesn't exist." . TextFormat::RESET);
                                    return true;
                                }
                            }
                            else{
                                $sender->sendMessage(TextFormat::RED . "You must provide at least one value." . TextFormat::RESET);
                                return true;
                            }
                        }
                        else{
                            $sender->sendMessage(TextFormat::RED . "You must specify a name." . TextFormat::RESET);
                            return true;
                        }
                        break;
                    case "reset":
                    case "r":
                        if(isset($args[1])){
                            if(isset($this->mines[$args[1]])){
                                if($this->mines[$args[1]]->isMineSet()){
                                    $this->mines[$args[1]]->resetMine();
                                    $sender->sendMessage("Mine is now resetting.");
                                    return true;
                                }
                                else{
                                    $sender->sendMessage(TextFormat::RED . "Mine has not been set." . TextFormat::RESET);
                                    return true;
                                }
                            }
                            else{
                                $sender->sendMessage(TextFormat::RED . "Mine doesn't exist." . TextFormat::RESET);
                                return true;
                            }
                        }
                        else{
                            $sender->sendMessage(TextFormat::RED . "You need to specify a name." . TextFormat::RESET);
                            return true;
                        }
                        break;
                    case "longreset":
                    case "lr":
                        $sender->sendMessage(TextFormat::RED . "Long resetting is no longer supported, if you need it use an older version." . TextFormat::RESET);
                        return true;
                        break;
                }
            }
        }
        else{
            $sender->sendMessage(TextFormat::RED . "You must specify the action to perform." . TextFormat::RESET);
            return true;
        }
        return false;
    }
    public function onBlockTap(PlayerInteractEvent $event){
        if(isset($this->sessions[$event->getPlayer()->getName()])){
            if(isset($this->sessions[$event->getPlayer()->getName()][1])){
                $a = $this->sessions[$event->getPlayer()->getName()][1];
                $b = $event->getBlock();
                $this->mines[$this->sessions[$event->getPlayer()->getName()][0]] = new Mine($this, new Vector3(min($a->getX(), $b->getX()), min($a->getY(), $b->getY()), min($a->getZ(), $b->getZ())), new Vector3(max($a->getX(), $b->getX()), max($a->getY(), $b->getY()), max($a->getZ(), $b->getZ())), $b->getLevel());
                $event->getPlayer()->sendMessage("Mine created.");
                unset($this->sessions[$event->getPlayer()->getName()]);
                $this->saveConfig();
            }
            else{
                $this->sessions[$event->getPlayer()->getName()][1] = new Vector3($event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ());
                $event->getPlayer()->sendMessage("Tap another block to create mine");
            }
        }
    }
    public function saveConfig(){
        $this->mineData->setAll([]);
        foreach($this->mines as $n => $mine){
            $this->mineData->set($n, [$mine->getA()->getX(), $mine->getB()->getX(), $mine->getA()->getY(), $mine->getB()->getY(), $mine->getA()->getZ(), $mine->getB()->getZ(), (count($mine->getData()) > 0 ? $mine->getData() : false) , $mine->getLevel()->getName()]);
        }
        $this->mineData->save();
    }
    public function parseMines(){
        foreach($this->mineData->getAll() as $n => $m){
            if($m[6] !== false){
                $this->mines[$n] = new Mine($this, new Vector3(min($m[0], $m[1]), min($m[2], $m[3]), min($m[4], $m[5])), new Vector3(max($m[0], $m[1]), max($m[2], $m[3]), max($m[4], $m[5])), $this->getServer()->getLevelByName($m[7]), $m[6]);
            }
            else{
                $this->mines[$n] = new Mine($this, new Vector3(min($m[0], $m[1]), min($m[2], $m[3]), min($m[4], $m[5])), new Vector3(max($m[0], $m[1]), max($m[2], $m[3]), max($m[4], $m[5])), $this->getServer()->getLevelByName($m[7]));
            }
        }
    }
    public function scheduleReset(MineResetTask $mineResetTask){
        $this->getServer()->getScheduler()->scheduleAsyncTask($mineResetTask);
    }
    /**
     * @return RegionBlocker
     */
    public function getRegionBlocker(){
        return $this->regionBlocker;
    }


}
