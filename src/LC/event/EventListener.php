<?php

namespace LC\event;

use LC\api\ItemManager;
use LC\block\RegisterBlocks;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as MG;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\sound\PopSound;

use Vecnavium\FormsUI\Form;
use Vecnavium\FormsUI\FormAPI;
use Vecnavium\FormsUI\SimpleForm;
use LC\LobbyCore;

class EventListener implements Listener
{

    private $plugin;
    private array $hiddenPlayers = [];

    public function onJoin(PlayerJoinEvent $event)
    {

        $player = $event->getPlayer();
        $name = $player->getName();

        $event->setJoinMessage("");
        $this->plugin = LobbyCore::getInstance();
        Server::getInstance()->broadcastMessage(str_replace(["{player}"], [$player->getName()], $this->plugin->getConfig()->get("Join-Message")));
        $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());

        $item1 = VanillaItems::GRAY_DYE();
        $item1->setCustomName("§7Hide Player (Hold / Right Click)");

        $item2 = VanillaBlocks::POPPY()->asItem();
        $item2->setCustomName("Cosmetic §7(Hold / Right Click)");

        $item3 = VanillaItems::COMPASS();
        $item3->setCustomName("§aServer Selector §7(Hold / Right Click)");

        $item4 = VanillaItems::POPPED_CHORUS_FRUIT();
        $item4->setCustomName("§dSocial §7(Hold / Right Click)");

        $item5 = VanillaBlocks::MOB_HEAD()->setSkullType(SkullType::PLAYER())->asItem();
        $item5->setCustomName("§eProfile §7(Hold / Right Click)");

        $player->getInventory()->setItem(0, $item1);
        $player->getInventory()->setItem(1, $item2);
        $player->getInventory()->setItem(4, $item3);
        $player->getInventory()->setItem(7, $item4);
        $player->getInventory()->setItem(8, $item5);
    }

    public function onQuit(PlayerQuitEvent $event){

        $player = $event->getPlayer();
        unset($this->hiddenPlayers[$player->getName()]);

        $event->setQuitMessage("");
        Server::getInstance()->broadcastMessage(str_replace(["{player}"], [$player->getName()], $this->plugin->getConfig()->get("Quit-Message")));
    }
	
    public function onClick(PlayerInteractEvent $event)
    {
    $player = $event->getPlayer();
    $item = $player->getInventory()->getItemInHand();
    $itemName = $item->getCustomName();

    switch ($itemName) {
        case "§7Hide Player (Hold / Right Click)":
        case "§aShow Player §7(Hold / Right Click)":
        $this->togglePlayerVisibility($player);
        break;

        case "§cCosmetic §7(Hold / Right Click)":
            $this->plugin->getServer()->getCommandMap()->dispatch($player, "report");
            break;

        case "§aServer Selector §7(Hold / Right Click)":
            LobbyCore::getInstance()->getUI()->getGames($player);
            break;

        case "§dSocial §7(Hold / Right Click)":
            LobbyCore::getInstance()->getUI()->getInfo($player);
            break;

        case "§eProfile §7(Hold / Right Click)":
            $this->plugin->getServer()->getCommandMap()->dispatch($player, "hub");
            break;
    }
}

    private function togglePlayerVisibility(Player $player)
	{
        $name = $player->getName();

        if (isset($this->hiddenPlayers[$name])) {

            unset($this->hiddenPlayers[$name]);
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                $player->showPlayer($p);
            }

            $newItem = VanillaItems::GRAY_DYE();
            $newItem->setCustomName("§7Hide Player (Hold / Right Click)");
            $player->getInventory()->setItem(0, $newItem);

            $player->sendMessage(MG::GREEN . "All players have been show.");
            $player->getWorld()->addParticle($player->getPosition(), new HugeExplodeParticle());
            $player->getWorld()->addSound($player->getPosition(), new PopSound());
        } else {

            $this->hiddenPlayers[$name] = true;
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                if ($p !== $player) {
                    $player->hidePlayer($p);
                }
            }

            $newItem = VanillaItems::LIME_DYE();
            $newItem->setCustomName("§aShow Player §7(Hold / Right Click)");
            $player->getInventory()->setItem(0, $newItem);

            $player->sendMessage(MG::RED . "All players have been hidden.");
            $player->getWorld()->addParticle($player->getPosition(), new HugeExplodeParticle());
            $player->getWorld()->addSound($player->getPosition(), new PopSound());
        }
    }
}
