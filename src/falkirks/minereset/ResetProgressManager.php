<?php
namespace falkirks\minereset;


use pocketmine\command\CommandSender;

class ResetProgressManager{
    /** @var  MineReset */
    private $api;

    /** @var  array */
    private $subscriptions;

    /**
     * ResetProgressManager constructor.
     * @param MineReset $api
     */
    public function __construct(MineReset $api){
        $this->api = $api;
        $this->subscriptions = [];
    }


    public function notifyProgress(string $progress, string $mineName){
        if(isset($this->subscriptions[$mineName])){
            foreach ($this->subscriptions[$mineName] as $sender){
                $sender->sendMessage("RESET {$mineName}: {$progress}");
            }
        }
    }

    public function notifyComplete(string $mineName){
        if(isset($this->getApi()->getMineManager()[$mineName])){
            $this->getApi()->getMineManager()[$mineName]->doneReset();
        }
        if(isset($this->subscriptions[$mineName])){
            foreach ($this->subscriptions[$mineName] as $sender){
                $sender->sendMessage("Reset of {$mineName} has completed.");
            }
            unset($this->subscriptions[$mineName]);
        }
    }

    public function addObserver(string $mineName, CommandSender $sender){
        if(!isset($this->subscriptions[$mineName])){
            $this->subscriptions[$mineName] = [];
        }
        $this->subscriptions[$mineName][] = $sender;
    }

    /**
     * @return MineReset
     */
    public function getApi(): MineReset{
        return $this->api;
    }


}