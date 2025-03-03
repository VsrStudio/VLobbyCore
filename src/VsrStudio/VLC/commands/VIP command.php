<?php

namespace VsrStudio\VLC\commands;

use pocketmine\entity\object\ItemEntity;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as MG;
use pocketmine\Server;
use pocketmine\plugin\Plugin;
use pocketmine\item\Item;

use VsrStudio\VLC\LobbyCore;

class HubCommand extends Command
{
    private $plugin;

    public function __construct()
    {
        parent::__construct("hub", "Teleport you to the server spawn!", null, ["hub", "lobby"]);
        $this->setPermission("lobbycore.command.hub");
    }

    public function execute(CommandSender $player, string $label, array $args)
    {
        if (!$player instanceof Player)return;
        
        $this->plugin = LobbyCore::getInstance();
        $player->teleport($player->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
        $player->getInventory()->clearALL();
        $player->getArmorInventory()->clearALL();
        $player->sendMessage(str_replace(["{player}"], [$player->getName()], $this->plugin->getConfig()->get("Hub-Message")));

        $item1 = VanillaBlocks::ENDER_CHEST()->asItem();
        $item1->setCustomName("Cosmetics");

        $item2 = VanillaBlocks::ANVIL()->asItem();
        $item2->setCustomName("ReportPlayer");

        $item3 = VanillaItems::BOOK();
        $item3->setCustomName("Informacion");
                
        $item3 = VanillaItems::COMPASS();
        $item3->setCustomName("Teleporter");

        $item4 = VanillaItems::POPPED_CHORUS_FRUIT();
        $item4->setCustomName("InfoUI");

        $item5 = VanillaItems::NETHER_STAR();
        $item5->setCustomName("Lobby");

        $player->getInventory()->setItem(0, $item1);
        $player->getInventory()->setItem(1, $item2);
        $player->getInventory()->setItem(4, $item3);
        $player->getInventory()->setItem(7, $item4);
        $player->getInventory()->setItem(8, $item5);
    }
}
