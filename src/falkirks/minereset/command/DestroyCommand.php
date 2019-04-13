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

    public function doDelete($name, CommandSender $sender){
        unset($this->getApi()->getMineManager()[$name]);
        unset($this->senders[$sender->getName()]);
        $sender->sendMessage("{$name[0]} has been destroyed.");
    }


    public function execute(CommandSender $sender, $commandLabel, array $args){
        if($sender->hasPermission("minereset.command.destroy")) {
            if (isset($args[0])) {
                if (isset($this->getApi()->getMineManager()[$args[0]])) {
                    if($sender instanceof Player && $this->formsSupported()){
                        $form = new class("Are you sure?", "You are about to delete the mine called {$args[0]}.") extends ModalForm {
                            public function onSubmit(Player $player, $response) : void{
                                if($response){
                                    $this->parent->doDelete($this->name, $player);
                                }
                            }
                        };
                        $form->parent = $this;
                        $form->name = $args[0];
                        $sender->sendForm($form);
                    }
                    else if (isset($args[1]) && isset($this->senders[$sender->getName()]) && $this->senders[$sender->getName()] === $args[1]) {
                        $this->doDelete($args[0], $sender);
                    } else {
                        $str = DestroyCommand::DESTROY_STRINGS[$this->offset];
                        $sender->sendMessage("Run: " . TextFormat::AQUA . "/mine destroy {$args[0]} $str" . TextFormat::RESET);
                        $sender->sendMessage("To destroy mines faster, you can edit the config file directly.");
                        $this->senders[$sender->getName()] = $str;

                        if ($this->offset === count(DestroyCommand::DESTROY_STRINGS) - 1) {
                            $this->offset = -1;
                        }

                        $this->offset++;
                    }
                } else {
                    $sender->sendMessage("{$args[0]} is not a valid mine.");
                }
            } else {
                $sender->sendMessage("Usage: /mine destroy <name>");
            }
        }
        else{
            $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
        }
    }
}