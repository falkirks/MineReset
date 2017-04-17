<?php
namespace falkirks\minereset\task;


use pocketmine\command\CommandSender;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Utils;

class AboutPullTask extends AsyncTask {

    const ABOUT_URL = "http://falkirks.com/pmabout.txt";

    /**
     * AboutPullTask constructor.
     * @param CommandSender $sender
     */
    public function __construct(CommandSender $sender){
        parent::__construct($sender);
    }


    public function onRun(){
        $this->setResult(Utils::getURL(AboutPullTask::ABOUT_URL));
    }

    public function onCompletion(Server $server){
        $sender = $this->fetchLocal($server);
        if($sender instanceof CommandSender){
            $result = $this->getResult();
            if($result !== false){
                $sender->sendMessage($this->getResult());
            }
            else{
                $sender->sendMessage("MineReset by Falkirks. This is a fancy plugin that allows you to make resettable mines.");
            }
        }
    }


}