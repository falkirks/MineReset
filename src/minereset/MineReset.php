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

class MineReset extends PluginBase implements CommandExecutor, Listener{
    public $config, $m, $s, $longReset;
    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "mines.yml", Config::YAML, array());
        $this->m = [];
        $this->parseMines();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->longReset = new LongReset($this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask($this->longReset, 2);
        $this->s = [];
    }
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        if(isset($args[0])){
            if(!$sender->hasPermission("minereset.commmand." . $args[0])){
                $sender->sendMessage("You do not have permission.");
                return true;
            }
            else{
                switch($args[0]){
                    case "create":
                        if($sender instanceof Player){
                            if(isset($args[1])){
                                if(!isset($this->m[$args[1]])){
                                    $this->s[$sender->getName()] = [$args[1]];
                                    $sender->sendMessage("Tap a block to set as first position...");
                                    return true;
                                }
                                else{
                                    $sender->sendMessage("That mine already exists.");
                                    return true;
                                }
                            }
                            else{
                                $sender->sendMessage("You must specify a name.");
                                return true;
                            }
                        }
                        else{
                            $sender->sendMessage("Please run command in game.");
                            return true;
                        }
                        break;
                    case "set":
                        if(isset($args[1])){
                            if(isset($args[3])){
                                if (isset($this->m[$args[1]])) {
                                    $sets = array_slice($args, 2);
                                    foreach ($sets as $key => $item) {
                                        if ( $key & 1 ) {
                                            $save[$sets[$key-1]] = $item;
                                        }
                                    }
                                    $this->m[$args[1]]->setData($save);
                                    $sender->sendMessage("Mine setted.");
                                    $this->saveConfig();
                                    return true;
                                }
                                else{
                                    $sender->sendMessage("Mine doesn't exist.");
                                    return true;
                                }
                            }
                            else{
                                $sender->sendMessage("You must provide at least one value.");
                                return true;
                            }
                        }
                        else{
                            $sender->sendMessage("You must specify a name.");
                            return true;
                        }
                        break;
                    case "reset":
                        if(isset($args[1])){
                            if(isset($this->m[$args[1]])){
                                if($this->m[$args[1]]->isMineSet()){
                                    $this->m[$args[1]]->resetMine();
                                    $sender->sendMessage("Mine has been reset.");
                                    return true;
                                }
                                else{
                                    $sender->sendMessage("Mine has not been set.");
                                    return true;
                                }
                            }
                            else{
                                $sender->sendMessage("Mine doesn't exist.");
                                return true;
                            }
                        }
                        else{
                            $sender->sendMessage("You need to specify a name.");
                            return true;
                        }
                        break;
                    case "longreset":
                        if(isset($args[1])){
                            if(isset($this->m[$args[1]])){
                                if($this->m[$args[1]]->isMineSet()){
                                    $this->m[$args[1]]->longReset();
                                    $sender->sendMessage("Mine is resetting...");
                                    return true;
                                }
                                else{
                                    $sender->sendMessage("Mine has not been set.");
                                    return true;
                                }
                            }
                            else{
                                $sender->sendMessage("Mine doesn't exist.");
                                return true;
                            }
                        }
                        else{
                            $sender->sendMessage("You need to specify a name.");
                            return true;
                        }
                        break;
                }
            }
        }
        else{
            $sender->sendMessage("You must specify the action to perform.");
            return true;
        }
    }
    public function onBlockTap(PlayerInteractEvent $event){
        if(isset($this->s[$event->getPlayer()->getName()])){
            if(isset($this->s[$event->getPlayer()->getName()][1])){
                $a = $this->s[$event->getPlayer()->getName()][1];
                $b = $event->getBlock();
                $this->m[$this->s[$event->getPlayer()->getName()][0]] = new Mine($this, new Vector3(min($a->getX(), $b->getX()), min($a->getY(), $b->getY()), min($a->getZ(), $b->getZ())), new Vector3(max($a->getX(), $b->getX()), max($a->getY(), $b->getY()), max($a->getZ(), $b->getZ())), $b->getLevel());
                $event->getPlayer()->sendMessage("Mine created.");
                unset($this->s[$event->getPlayer()->getName()]);
                $this->saveConfig();
            }
            else{
                $this->s[$event->getPlayer()->getName()][1] = new Vector3($event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ());
                $event->getPlayer()->sendMessage("Tap another block to create mine");
            }
        }
    }
    public function saveConfig(){
        foreach($this->m as $n => $mine){
            $this->config->set($n, [$mine->getA()->getX(), $mine->getB()->getX(), $mine->getA()->getY(), $mine->getB()->getY(), $mine->getA()->getZ(), $mine->getB()->getZ(), (count($mine->getData()) > 0 ? $mine->getData() : false) , $mine->getLev()->getName()]);
        }
        $this->config->save();
    }
    public function parseMines(){
        foreach($this->config->getAll() as $n => $m){
            if($m[6] !== false){
                $this->m[$n] = new Mine($this, new Vector3(min($m[0], $m[1]), min($m[2], $m[3]), min($m[4], $m[5])), new Vector3(max($m[0], $m[1]), max($m[2], $m[3]), max($m[4], $m[5])), $this->getServer()->getLevelByName($m[7]), $m[6]);
            }
            else{
                $this->m[$n] = new Mine($this, new Vector3(min($m[0], $m[1]), min($m[2], $m[3]), min($m[4], $m[5])), new Vector3(max($m[0], $m[1]), max($m[2], $m[3]), max($m[4], $m[5])), $this->getServer()->getLevelByName($m[7]));
            }
        }
    }
}