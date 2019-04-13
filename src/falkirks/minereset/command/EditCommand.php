<?php

namespace falkirks\minereset\command;


use falkirks\minereset\task\AboutPullTask;
use Frago9876543210\EasyForms\elements\custom\Dropdown;
use Frago9876543210\EasyForms\elements\custom\Input;
use Frago9876543210\EasyForms\elements\custom\Label;
use Frago9876543210\EasyForms\forms\CustomForm;
use Frago9876543210\EasyForms\forms\ModalForm;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EditCommand extends SubCommand{
    public function execute(CommandSender $sender, $commandLabel, array $args){
        if($sender->hasPermission("minereset.command.edit")) {
            if($sender instanceof Player && $this->formsSupported()){
                $sender->sendForm(new class("Mine: a", [
                    new Dropdown("Select product", ["beer", "cheese", "cola"]),
                    new Input("Mine name", "a"),
                    new Input("Reset interval", "-1"),
                    new Label("Reset interval is in seconds"), //popElement() does not work with label
                    new Input("Warp name", ""),
                    new Label("Name of the warp to link with the mine"),
                ]) extends CustomForm {
                    public function onSubmit(Player $player, $response) : void{
                        parent::onSubmit($player, $response);
                        $player->sendMessage("cool!");
                    }
                });
            }
            else {
                $sender->sendMessage(TextFormat::RED . "You must install EasyForms to use this command." . TextFormat::RESET);
            }
        }
        else{
            $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
        }


    }
}