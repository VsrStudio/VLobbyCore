<?php

namespace VsrStudio\VLC\event;

use pocketmine\item\StringToItemParser;
use pocketmine\item\LegacyStringToItemParser;
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
use pocketmine\block\utils\DyeColor;
use pocketmine\item\VanillaItems;
use pocketmine\item\Dye;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as MG;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\sound\PopSound;
use pocketmine\block\utils\MobHeadType;

use jojoe77777\FormAPI\Form;
use jojoe77777\FormAPI\FormAPI;
use jojoe77777\FormAPI\SimpleForm;
use VsrStudio\VLC\LobbyCore;

class EventListener implements Listener
{

    private $plugin;
    private array $hiddenPlayers = [];

    public function onJoin(PlayerJoinEvent $event) : void {
        $player = $event->getPlayer();
        $event->setJoinMessage("");

        Server::getInstance()->broadcastMessage(str_replace(
            ["{player}"],
            [$player->getName()],
            $this->plugin->getConfig()->get("Join-Message")
        ));
        $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());

        $items = $this->plugin->getConfig()->get("items", []);
        foreach($items as $key => $data){
            $item = $this->parseItem($data["item"] ?? "stone");
            if($item === null) continue;

            if(isset($data["name"])) $item->setCustomName($data["name"]);
            $slot = $data["slot"] ?? 0;

            $player->getInventory()->setItem($slot, $item);
        }
    }

    public function onQuit(PlayerQuitEvent $event) : void {
        $player = $event->getPlayer();
        unset($this->hiddenPlayers[$player->getName()]);

        $event->setQuitMessage("");
        Server::getInstance()->broadcastMessage(str_replace(
            ["{player}"],
            [$player->getName()],
            $this->plugin->getConfig()->get("Quit-Message")
        ));
    }

    public function onClick(PlayerInteractEvent $event) : void {
		$player = $event->getPlayer();
			$item = $player->getInventory()->getItemInHand();
		$name = $item->getCustomName();

		$items = $this->plugin->getConfig()->get("items", []);
		foreach($items as $data){
			if(isset($data["name"]) && $data["name"] === $name){
				$action = $data["action"] ?? null;
				if($action === null) return;

				switch ($action) {
					case "toggle-visibility":
					$this->togglePlayerVisibility($player);
					break;

					case "command":
					if(isset($data["command"])){
						$this->plugin->getServer()->getCommandMap()->dispatch($player, $data["command"]);
					}
					break;

					case "open-ui":
					if(isset($data["ui"])){
						switch ($data["ui"]) {
							case "server-selector":
                                LobbyCore::getUI()->games($player);
                                break;
                            case "social":
                                LobbyCore::getUI()->SocialMenu($player);
                                break;
                            case "profile":
                                LobbyCore::getUI()->openProfileForm($player);
                                break;
						}
                    }
                    break;
				}
			}
		}
	}

    private function togglePlayerVisibility(Player $player) : void {
        $name = $player->getName();

        if (isset($this->hiddenPlayers[$name])) {
            unset($this->hiddenPlayers[$name]);
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                $player->showPlayer($p);
            }

            $item = $this->parseItem("dye:gray");
            if($item !== null){
                $item->setCustomName("§7Hide Player §7(Hold / Right Click)");
                $player->getInventory()->setItem(0, $item);
            }

            $player->sendMessage(MG::GREEN . "All players have been shown.");
        } else {
            $this->hiddenPlayers[$name] = true;
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                if ($p !== $player) $player->hidePlayer($p);
            }

            $item = $this->parseItem("dye:lime");
            if($item !== null){
                $item->setCustomName("§aShow Player §7(Hold / Right Click)");
                $player->getInventory()->setItem(0, $item);
            }

            $player->sendMessage(MG::RED . "All players have been hidden.");
        }

        $player->getWorld()->addParticle($player->getPosition(), new HugeExplodeParticle());
        $player->getWorld()->addSound($player->getPosition(), new PopSound());
    }

    /**
     * Parse item string ke objek Item (support StringToItemParser & LegacyStringToItemParser)
     */
    private function parseItem(string $name){
        $parser = StringToItemParser::getInstance();
        $legacy = LegacyStringToItemParser::getInstance();

        $item = $parser->parse($name);
        if($item !== null){
            return $item;
        }
        return $legacy->parse($name);
    }
}
