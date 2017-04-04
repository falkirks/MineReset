<?php
namespace minereset;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class MineReset extends PluginBase implements Listener{

    /** @var string[]|Vector3[] $sessions */
    public $sessions = [];
    /** @var Config $mineData */
    public $mineData;
    /** @var Mine[] $mines */
    public $mines = [];
    /** @var RegionBlocker $regionBlocker */
    private $regionBlocker;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->mineData = new Config($this->getDataFolder() . "mines.yml", Config::YAML, []);
        $this->parseMines();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->regionBlocker = new RegionBlocker($this);
    }

    /**
     * @param CommandSender $sender
     * @param Command $cmd
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        if(isset($args[0])){
            if(!$sender->hasPermission("minereset.commmand." . strtolower($args[0]))){
                $sender->sendMessage(TextFormat::RED . "You do not have permission." . TextFormat::RESET);
                return true;
            }else{
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
                                $sender->sendMessage("Usage: /mine create <name>");
                                return true;
                            }
                        }
                        else{
                            $sender->sendMessage("You must use this command in-game");
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
                            $sender->sendMessage("Usage: /mine destroy <mine>");
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
                                            if(strpos($item, "%")) {
                                                $sender->sendMessage(TextFormat::RED . "Your format string looks incorrect." . TextFormat::RESET);
                                                return true;
                                            }
                                            if ($key & 1) {
                                                if (isset($save[$sets[$key - 1]])) {
                                                    $save[$sets[$key - 1]] += $item;
                                                } else {
                                                    $save[$sets[$key - 1]] = $item;
                                                }
                                            }
                                        }
                                        $this->mines[$args[1]]->setData($save);
                                        $sender->sendMessage("Mine blocks have been saved");
                                        $this->saveConfig();
                                        return true;
                                    }
                                    else{
                                        $sender->sendMessage(TextFormat::RED . "Your format string looks incorrect." . TextFormat::RESET);
                                        return true;
                                    }
                                }
                                else{
                                    $sender->sendMessage(TextFormat::RED . "Mine doesn't exist." . TextFormat::RESET);
                                    return true;
                                }
                            }
                            else{
                                $sender->sendMessage(TextFormat::RED . "You must provide at least one block with a chance value." . TextFormat::RESET);
                                return true;
                            }
                        }
                        else{
                            $sender->sendMessage("Usage: /mine set <mine> <block> <chance>");
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
                    case "list":
                    case "l":
                        $list = "All of the mines are named as follows:\n";
                        ksort($this->mines, SORT_NATURAL);
                        foreach (array_keys($this->mines) as $mine) {
                            $list .= $mine . ", ";
                        }
                        rtrim($list, ", \t\n");
                        $sender->sendMessage($list);
                        break;
                    case "reset-all":
                        $i = 0;
                        foreach($this->mines as $mine) {
                            if($mine->isMineSet()) {
                                $mine->resetMine();
                                $i++;
                            }
                        }
                        $sender->sendMessage("Resetting {$i} mines.");
                        return true;
                        break;
                }
            }
        }
        else{
            return false;
        }
        return true;
    }

    /**
     * @priority LOW
     * @ignoreCancelled true
     *
     * @param PlayerInteractEvent $event
     */
    public function onBlockTap(PlayerInteractEvent $event){
        if(isset($this->sessions[$event->getPlayer()->getName()])){
            if(isset($this->sessions[$event->getPlayer()->getName()][1])){
                /** @var Vector3 $a */
                $a = $this->sessions[$event->getPlayer()->getName()][1];
                $b = $event->getBlock();
                $this->mines[$this->sessions[$event->getPlayer()->getName()][0]] = new Mine(
                    $this,
                    $this->sessions[$event->getPlayer()->getName()][0],
                    new Vector3(min($a->getX(), $b->getX()), min($a->getY(), $b->getY()), min($a->getZ(), $b->getZ())),
                    new Vector3(max($a->getX(), $b->getX()), max($a->getY(), $b->getY()), max($a->getZ(), $b->getZ())),
                    $b->getLevel()->getId()
                );
                $event->getPlayer()->sendMessage("Mine created.");
                unset($this->sessions[$event->getPlayer()->getName()]);
                $this->saveConfig();
            }
            else{
                $this->sessions[$event->getPlayer()->getName()][1] = new Vector3($event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ());
                $event->getPlayer()->sendMessage("Tap another block to create mine");
            }
            $event->setCancelled();
        }
    }

    public function saveConfig(){
        $this->mineData->setAll([]);
        foreach($this->mines as $n => $mine){
            $this->mineData->set($n, [
                $mine->getA()->getX(),
                $mine->getB()->getX(),
                $mine->getA()->getY(),
                $mine->getB()->getY(),
                $mine->getA()->getZ(),
                $mine->getB()->getZ(),
                (count($mine->getData()) > 0 ? $mine->getData() : false),
                $mine->getLevel()->getName()
            ]);
        }
        $this->mineData->save();
    }

    public function parseMines(){
        foreach($this->mineData->getAll() as $n => $m){
            if(!$this->getServer()->getLevelByName($m[7]) instanceof Level) {
                if(!$this->getServer()->loadLevel($m[7])) {
                    $this->getLogger()->error("The world '{$m[7]}' of mine '{$n}' is invalid");
                    continue;
                }
                $this->getLogger()->info("Loaded level '{$m[7]}' for mine '{$n}'");
            }
            if(!is_array($m[6])) {
                $this->getLogger()->error("The block settings for mine '{$n}' are incorrect");
                continue;
            }
            $this->mines[$n] = new Mine($this,
                $n,
                new Vector3(min($m[0], $m[1]), min($m[2], $m[3]), min($m[4], $m[5])),
                new Vector3(max($m[0], $m[1]), max($m[2], $m[3]), max($m[4], $m[5])),
                $this->getServer()->getLevelByName($m[7])->getId(),
                $m[6]
            );
        }
    }

    /**
     * @param MineResetTask $mineResetTask
     */
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
