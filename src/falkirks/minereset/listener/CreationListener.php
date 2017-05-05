<?php
namespace falkirks\minereset\listener;


use falkirks\minereset\MineReset;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CreationListener implements Listener {
    /** @var  MineReset */
    private $api;

    /** @var  MineCreationSession[] */
    private $sessions;


    /**
     * CreationListener constructor.
     * @param MineReset $api
     */
    public function __construct(MineReset $api){
        $this->api = $api;
        $this->sessions = [];
    }

    /**
     * @priority LOW
     * @ignoreCancelled true
     *
     * @param PlayerInteractEvent $event
     */
    public function onBlockTap(PlayerInteractEvent $event){
        $session = $this->getPlayerSession($event->getPlayer());

        if($session !== null){
            if($session->getLevel() === null || $session->getLevel()->getId() === $event->getPlayer()->getLevel()->getId()) {
                $session->setNextPoint($event->getBlock());
                $session->setLevel($event->getPlayer()->getPosition()->getLevel());

                if($session->canGenerate()){
                    $mine = $session->generate($this->getApi()->getMineManager());
                    $event->getPlayer()->sendMessage("You have created a mine called " . $mine->getName() . ".");
                    $event->getPlayer()->sendMessage("You can set it using /mine set " . $mine->getName() . " <data>");
                    unset($this->sessions[array_search($session, $this->sessions)]);
                }
                else{
                    $event->getPlayer()->sendMessage("You have set position A. Tap another block to set position B.");
                }
            }
            else{
                $event->getPlayer()->sendMessage(TextFormat::RED . "Failed to create mine due to level switch". TextFormat::RESET);
                unset($this->sessions[array_search($session, $this->sessions)]);
            }
        }
    }

    /**
     * @return MineReset
     */
    public function getApi(): MineReset{
        return $this->api;
    }

    public function playerHasSession(Player $player): bool {
        foreach ($this->sessions as $session){
            if($session->getPlayer()->getName() === $player->getName()){
                return true;
            }
        }
        return false;
    }

    public function getPlayerSession(Player $player){
        foreach ($this->sessions as $session){
            if($session->getPlayer()->getName() === $player->getName()){
                return $session;
            }
        }
        return null;
    }


    public function addSession(MineCreationSession $session): bool {
        if(!$this->playerHasSession($session->getPlayer())) {
            $this->sessions[] = $session;
            return true;
        }
        return false;
    }


}