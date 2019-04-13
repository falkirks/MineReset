<?php
namespace falkirks\minereset\command;


use falkirks\minereset\MineReset;
use Frago9876543210\EasyForms\forms\ModalForm;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DestroyCommand extends SubCommand{

    const DESTROY_STRINGS = [
        "a",
        "b",
        "c",
        "5",
        "7",
        "-f",
        "DEATH",
        "yes",
        "15",
        "y"
    ];

    private $offset;
    private $senders;

    public function __construct(MineReset $mineReset){
        parent::__construct($mineReset);
        $this->offset = 0;
        $this->senders = [];
    }

    public function doDelete(CommandSender $sender, $name){
        unset($this->getApi()->getMineManager()[$name]);
        unset($this->senders[$sender->getName()]);
        $sender->sendMessage("{$name[0]} has been destroyed.");
    }

    private function formDelete(CommandSender $sender, $name){
        $form = new class("Are you sure?", "You are about to delete the mine called $name.") extends ModalForm {
            public function onSubmit(Player $player, $response) : void{
                if($response){
                    $this->parent->doDelete($player, $this->name);
                }
            }
        };
        $form->parent = $this;
        $form->name = $name;
        $sender->sendForm($form);
    }

    private function basicDelete(CommandSender $sender, $name){
        $str = DestroyCommand::DESTROY_STRINGS[$this->offset];
        $sender->sendMessage("Run: " . TextFormat::AQUA . "/mine destroy $name $str" . TextFormat::RESET);
        $sender->sendMessage("To destroy mines faster, you can edit the config file directly.");
        $this->senders[$sender->getName()] = $str;

        if ($this->offset === count(DestroyCommand::DESTROY_STRINGS) - 1) {
            $this->offset = -1;
        }

        $this->offset++;
    }


    public function execute(CommandSender $sender, $commandLabel, array $args){
        if(!$sender->hasPermission("minereset.command.destroy"))
            return $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);

        if (!isset($args[0]))
            return $sender->sendMessage("Usage: /mine destroy <name>");

        $name = $args[0];

        if(!isset($this->getApi()->getMineManager()[$name]))
            return $sender->sendMessage("{$args[0]} is not a valid mine.");

        if($sender instanceof Player && $this->formsSupported()){
            $this->formDelete($sender, $name);
        }
        else if (isset($args[1]) && isset($this->senders[$sender->getName()]) && $this->senders[$sender->getName()] === $args[1]) {
            $this->doDelete($sender, $name);
        } else {
            $this->basicDelete($sender, $name);
        }

        return true;
    }
}