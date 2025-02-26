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
        $item1->setCustomName("§7Hide Player");

        $item2 = VanillaBlocks::ANVIL()->asItem();
        $item2->setCustomName("ReportPlayer");

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

    public function onQuit(PlayerQuitEvent $event){

        $player = $event->getPlayer();
        unset($this->hiddenPlayers[$player->getName()]);

        $event->setQuitMessage("");
        Server::getInstance()->broadcastMessage(str_replace(["{player}"], [$player->getName()], $this->plugin->getConfig()->get("Quit-Message")));
    }
	
    public function onClick(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $itn = $player->getInventory()->getItemInHand()->getCustomName();
        if ($itn == "Cosmetics") {
            LobbyCore::getInstance()->getUI()->getCosmetics($player);
        }
	if ($itn == "ReportPlayer"){
            $this->plugin->getServer()->getCommandMap()->dispatch($player, "report");
        }
	if ($itn == "Teleporter") {
            LobbyCore::getInstance()->getUI()->getGames($player);
        }
        if ($itn == "InfoUI") {
            LobbyCore::getInstance()->getUI()->getInfo($player);
        }
	if ($itn == "Lobby"){
            $this->plugin->getServer()->getCommandMap()->dispatch($player, "hub");
        }
    }

    private function togglePlayerVisibility(Player $player)
	{
        $name = $player->getName();

        if (isset($this->hiddenPlayers[$name])) {
            // Jika pemain sudah menyembunyikan orang lain, maka tampilkan kembali
            unset($this->hiddenPlayers[$name]);
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                $player->showPlayer($p);
            }

            // Ubah item menjadi Gray Dye (Pemain terlihat)
            $newItem = VanillaItems::GRAY_DYE();
            $newItem->setCustomName("§7Hide Player");
            $player->getInventory()->setItem(0, $newItem);

            $player->sendMessage(MG::GREEN . "Semua pemain telah ditampilkan.");
            $player->getWorld()->addParticle($player->getPosition(), new HugeExplodeParticle());
            $player->getWorld()->addSound($player->getPosition(), new PopSound());
        } else {
            // Jika pemain belum menyembunyikan, maka sembunyikan
            $this->hiddenPlayers[$name] = true;
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                if ($p !== $player) {
                    $player->hidePlayer($p);
                }
            }

            // Ubah item menjadi Lime Dye (Pemain tersembunyi)
            $newItem = VanillaItems::LIME_DYE();
            $newItem->setCustomName("§aShow Player");
            $player->getInventory()->setItem(0, $newItem);

            $player->sendMessage(MG::RED . "Semua pemain telah disembunyikan.");
            $player->getWorld()->addParticle($player->getPosition(), new HugeExplodeParticle());
            $player->getWorld()->addSound($player->getPosition(), new PopSound());
        }
    }
}
