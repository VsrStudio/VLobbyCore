<?php

namespace VsrStudio\VLC\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as MG;

class VIPChatCommand extends Command
{
    public function __construct()
    {
        parent::__construct("vchat", "Send VIP chat messages", null, ["vc"]);
        $this->setPermission("vipchat.command");
    }

    public function execute(CommandSender $sender, string $label, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(MG::RED . "This command can only be used in game!");
            return;
        }

        if (!$this->testPermission($sender)) {
            $sender->sendMessage(MG::RED . "You do not have permission to use VIP chat!");
            return;
        }

        if (count($args) < 1) {
            $sender->sendMessage(MG::YELLOW . "use: /vchat <message>");
            return;
        }
        $message = implode(" ", $args);
        $vipMessage = MG::GOLD . "VC > " . MG::WHITE . $sender->getName() . ": " . MG::AQUA . $message;
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if ($player->hasPermission("vipchat.command")) {
                $player->sendMessage($vipMessage);
            }
        }
    }
}
