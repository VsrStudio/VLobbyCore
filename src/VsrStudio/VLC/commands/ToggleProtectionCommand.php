<?php

namespace VsrStudio\VLC\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use VsrStudio\VLC\event\Protection;

class ToggleProtectionCommand extends Command
{
    public function __construct()
    {
        parent::__construct("protection", "Protection On/Off", null, ["ptn"]);
        $this->setPermission("protection.command");
    }

    public function execute(CommandSender $sender, string $label, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(MG::RED . "This command can only be used in game!");
            return;
        }

        if (!$this->testPermission($sender)) {
            $sender->sendMessage(MG::RED . "You do not have permission to use!");
            return;
        }

        $status = Protection::toggleProtection();
        $message = $status ? "§aProteksi area lobby diaktifkan!" : "§cProteksi area lobby dimatikan!";
        $sender->sendMessage($message);
    }
}
