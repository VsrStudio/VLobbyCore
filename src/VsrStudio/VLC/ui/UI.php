<?php

namespace VsrStudio\VLC\ui;

use pocketmine\command\Command;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\network\mcpe\protocol\TransferPacket;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

use VsrStudio\VLC\libs\libEco\libEco;
use VsrStudio\VLC\libs\libForm\Form;
use VsrStudio\VLC\libs\libForm\FormAPI;
use VsrStudio\VLC\libs\libForm\SimpleForm;
use VsrStudio\VLC\libs\libForm\CustomForm;
use jasonw4331\libpmquery\PMQuery;
use jasonw4331\libpmquery\PMQueryException;

use VsrStudio\VLC\LobbyCore;

class UI {

    private LobbyCore $plugin;
    private Config $config;
    private string $dataFolder;

    public function __construct(LobbyCore $plugin){
        $this->plugin = $plugin;
        $this->dataFolder = $plugin->getDataFolder();
        $this->config = new Config($plugin->getDataFolder() . "config.yml", Config::YAML);
    }

    public function onJoin(PlayerJoinEvent $event): void {
    $player = $event->getPlayer();
    $name = $player->getName();
    
    $playerFile = $this->getPlayerConfig($name);

    if (!$playerFile->exists("Friend")) {
        $playerFile->set("Friend", []);
    }
    if (!$playerFile->exists("Invitations")) {
        $playerFile->set("Invitations", []);
    }
    if (!$playerFile->exists("blocked")) {
        $playerFile->set("blocked", false);
    }
    $playerFile->save();
}

public function onQuit(PlayerQuitEvent $event): void {
    $player = $event->getPlayer();
    $name = $player->getName();
    
    $playerFile = $this->getPlayerConfig($name);
    $friends = $playerFile->get("Friend", []);

    foreach ($friends as $friend) {
        $friendPlayer = Server::getInstance()->getPlayerExact($friend);
        if ($friendPlayer !== null) {
            $friendPlayer->sendMessage("$name is now offline.");
        }
    }
}

public function openFriendMenu(Player $player): void {    
    $friendCount = $this->getFriendCount($player);    
    $requestCount = $this->getFriendRequestCount($player);    

    $form = new SimpleForm(function (Player $player, ?int $data = null) {    
        if ($data === null) return;    
        switch ($data) {    
            case 0:    
                $this->openFriendList($player);    
                break;    
            case 1:    
                $this->openInviteFriend($player);    
                break;    
            case 2:    
                $this->openFriendRequests($player);    
                break;    
        }    
    });    

    $form->setTitle("Friend Menu");    
    $form->addButton("Manage Friends\nYou have $friendCount friends");    
    $form->addButton("Add Friend");    
    $form->addButton("Friend Requests ($requestCount)");    
    $form->addButton("Close");    

    $player->sendForm($form);    
}    

private function getFriendCount(Player $player): int {    
    return count($this->getPlayerConfig($player->getName())->get("Friend", []));    
}    

private function getFriendRequestCount(Player $player): int {    
    return count($this->getPlayerConfig($player->getName())->get("Invitations", []));    
}    

private function openFriendList(Player $player): void {    
    $playerFile = $this->getPlayerConfig($player->getName());    
    $friends = $playerFile->get("Friend", []);    

    $form = new SimpleForm(function (Player $player, ?int $data = null) use ($friends) {    
        if ($data === null || !isset($friends[$data])) return;    
        $this->removeFriend($player, $friends[$data]);
    });    

    $form->setTitle("Friend List");    
    if (empty($friends)) {    
        $form->setContent("You don't have any friends yet.");    
    } else {    
        foreach ($friends as $friend) {    
            $form->addButton("$friend\nClick to remove");    
        }    
    }    
    $player->sendForm($form);    
}
    
  private function openInviteFriend(Player $player): void {
    $form = new CustomForm(function (Player $player, ?array $data) {
        if ($data === null || trim($data[0]) === "") return;
        
        $targetName = trim($data[0]);
        $this->sendFriendRequest($player, $targetName);
    });

    $form->setTitle("Add Friend");
    $form->addInput("Enter the name of the player you want to add:", "Player Name");

    $player->sendForm($form);
}

/**
 * Mengirim permintaan pertemanan ke pemain lain.
 */
private function sendFriendRequest(Player $player, string $targetName): void {
    $target = Server::getInstance()->getPlayerExact($targetName);
    $playerFile = $this->getPlayerConfig($player->getName());
    $targetFile = $this->getPlayerConfig($targetName);

    if (strtolower($player->getName()) === strtolower($targetName)) {
        $player->sendMessage("§cYou cannot add yourself as a friend!");
        return;
    }

    if (!file_exists($this->dataFolder . DIRECTORY_SEPARATOR . "$targetName.yml")) {
        $player->sendMessage("§cPlayer not found.");
        return;
    }

    $friends = $playerFile->get("Friend", []);
    $requests = $targetFile->get("Invitations", []);

    if (in_array($targetName, $friends, true)) {
        $player->sendMessage("§aYou are already friends with $targetName.");
        return;
    }

    if (in_array($player->getName(), $requests, true)) {
        $player->sendMessage("§eYou have already sent a friend request to $targetName.");
        return;
    }

    $requests[] = $player->getName();
    $targetFile->set("Invitations", $requests);
    $targetFile->save();

    $player->sendMessage("§aFriend request sent to $targetName.");

    if ($target !== null) {
        $target->sendMessage("§eYou have received a friend request from {$player->getName()}.");
    }
}

private function openFriendRequests(Player $player): void {    
    $playerFile = $this->getPlayerConfig($player->getName());    
    $requests = $playerFile->get("Invitations", []);    

    $form = new SimpleForm(function (Player $player, ?int $data = null) use ($requests) {    
        if ($data === null || !isset($requests[$data])) return;    
        $this->acceptFriendRequest($player, $requests[$data]);    
    });    

    $form->setTitle("Friend Requests");    
    if (empty($requests)) {    
        $form->setContent("No pending friend requests.");    
    } else {    
        foreach ($requests as $request) {    
            $form->addButton("Accept from $request");    
        }    
    }    
    $player->sendForm($form);    
}    

private function acceptFriendRequest(Player $player, string $friend): void {
    $playerFile = $this->getPlayerConfig($player->getName());
    $friendFile = $this->getPlayerConfig($friend);

    $friends = $playerFile->get("Friend", []);
    $requests = $playerFile->get("Invitations", []);

    if (!in_array($friend, $requests, true)) {
        $player->sendMessage("§c$friend did not send you a friend request.");
        return;
    }

    if (!in_array($friend, $friends, true)) {
        $friends[] = $friend;
    }

    $friendFriends = $friendFile->get("Friend", []);
    if (!in_array($player->getName(), $friendFriends, true)) {
        $friendFriends[] = $player->getName();
    }

    unset($requests[array_search($friend, $requests, true)]);

    $playerFile->set("Friend", array_values($friends));
    $playerFile->set("Invitations", array_values($requests));
    $playerFile->save();

    $friendFile->set("Friend", array_values($friendFriends));
    $friendFile->save();

    $player->sendMessage("§aYou are now friends with $friend.");
    $target = Server::getInstance()->getPlayerExact($friend);
    if ($target !== null) {
        $target->sendMessage("§aYou are now friends with {$player->getName()}.");
    }
}

private function removeFriend(Player $player, string $friend): void {
    $playerFile = $this->getPlayerConfig($player->getName());
    $friendFile = $this->getPlayerConfig($friend);

    $friends = $playerFile->get("Friend", []);
    $friendFriends = $friendFile->get("Friend", []);

    if (!in_array($friend, $friends, true)) {
        $player->sendMessage("§cYou are not friends with $friend.");
        return;
    }

    unset($friends[array_search($friend, $friends, true)]);
    unset($friendFriends[array_search($player->getName(), $friendFriends, true)]);

    $playerFile->set("Friend", array_values($friends));
    $playerFile->save();

    $friendFile->set("Friend", array_values($friendFriends));
    $friendFile->save();

    $player->sendMessage("§eYou removed $friend from your friend list.");
    $target = Server::getInstance()->getPlayerExact($friend);
    if ($target !== null) {
        $target->sendMessage("§e{$player->getName()} removed you from their friend list.");
    }
}

/**
 * Fungsi untuk mendapatkan konfigurasi pemain dengan cara yang lebih efisien.
 */
private function getPlayerConfig(string $playerName): Config {
    return new Config($this->dataFolder . DIRECTORY_SEPARATOR . "$playerName.yml", Config::YAML);
}

    public function SocialMenu(Player $player){
        $form = new SimpleForm(function(Player $player, int $data = null){
            if($data === null){
                return true;
            }    switch($data){
                case 0:
                    $this->plugin->getServer()->getCommandM()->dispatch($player, "party");
                break;
                case 1:
                $this->openFriendMenu($player);
                break;
                case 2;
                break;
            }
        });
        $form->setTitle("§dSocial");
        $form->setContent("");
        $form->addButton("§lPartis");
        $form->addButton("§lFriend");
        $form->addButton("§cClose");
        $form->sendToPlayer($player);
    }

    /**
     * Function to get number of players from another server using libpmquery
     */
    private function pingServer(string $ip, int $port): int {
        try {
            $query = PMQuery::query($ip, $port);
            if(isset($query["Players"])){
                return (int)$query["Players"];
            }
        } catch (PMQueryException $e) {
            $this->plugin->getLogger()->warning("Gagal ping server $ip:$port - " . $e->getMessage());
        }
        return -1;
    }

    private function transferToServer(Player $player, string $ip, int $port): void {
        $pk = new TransferPacket();
        $pk->address = $ip;
        $pk->port = $port;
        $pk->reloadWorld = false;
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function games(Player $player): void {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) return;

            $games = $this->config->get("games", []);
            if (!isset($games[$data])) return;

            $gameConfig = $games[$data];

            if ($gameConfig["type"] === "world") {
                $totalPlayers = $this->getWorldPlayerCount($gameConfig["worlds"] ?? []);
                $player->sendMessage("§l§c[Game] > §r§cSedang Mentransfer Ke " . ucfirst($data) . "!");
                $player->sendMessage("§l§c[Game] > §r§bPemain di dalam: $totalPlayers");

                if (isset($gameConfig["command"])) {
                    Server::getInstance()->dispatchCommand($player, $gameConfig["command"]);
                }
            } elseif ($gameConfig["type"] === "server") {
                $ip = $gameConfig["ip"] ?? null;
                $port = $gameConfig["port"] ?? null;
                if ($ip && $port) {
                    $totalPlayers = $this->pingServer($ip, (int)$port);
                    if ($totalPlayers !== -1) {
                        $player->sendMessage("§l§c[Game] > §r§cMenghubungkan ke server $ip:$port...");
                        $player->sendMessage("§l§c[Game] > §r§bPemain di dalam: $totalPlayers");
                        $this->transferToServer($player, $ip, (int)$port);
                    } else {
                        $player->sendMessage("§l§c[Game] > §r§cServer sedang offline!");
                    }
                }
            }
        });

        $form->setTitle("§l§aMiniGames");
        $form->setContent("Pilih game untuk teleport");

        $games = $this->config->get("games", []);
        foreach ($games as $game => $info) {
            $totalPlayers = 0;
            $status = "§r§cOffline";

            if ($info["type"] === "world") {
                $totalPlayers = $this->getWorldPlayerCount($info["worlds"] ?? []);
            } elseif ($info["type"] === "server") {
                if (isset($info["ip"]) && isset($info["port"])) {
                    $totalPlayers = $this->pingServer($info["ip"], (int)$info["port"]);
                }
            }

            if ($totalPlayers > 0) {
                $status = "$totalPlayers §r§bPlayers§r";
            }

            $buttonText = "§l" . ucfirst($game) . "\n" . $status;
            $form->addButton($buttonText, 0, "textures/ui/worldsIcon.png", $game);
        }

        $player->sendForm($form);
    }

    private function getWorldPlayerCount(array $worldNames): int {
        $totalPlayers = 0;
        foreach ($worldNames as $worldName) {
            $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
            if ($world) {
                $totalPlayers += count($world->getPlayers());
            }
        }
        return $totalPlayers;
    }
    
    public function openProfileForm(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data): void {
            if ($data !== null) {
                // NOOP
            }
        });

        libEco::myMoney($player,
            function(float $money) use ($player, $form): void {
                $name = $player->getName();
                $rank = $this->getRank($player);
                $expLevel = $player->getXpManager()->getXpLevel();
                $date = date("d/m/Y H:i:s");
                $ping = $this->getPing($player);
                $firstPlayedDate = $this->getFormattedFirstPlayedDate($player);
                $coords = $this->getCoords($player);

                $form->setTitle(TF::BOLD . TF::RED . "YOUR PROFILE");
                $content = "This is your profile on the server:\n\n" .
                TF::GRAY . $date . "\n" .
                TF::WHITE . "Name : " . TF::GREEN . $name . "\n" .
                TF::WHITE . "Rank : " . TF::GREEN . $rank . "\n" .
                TF::WHITE . "Money : " . TF::GREEN . $money . "\n" .
                TF::WHITE . "Ping : " . TF::GREEN . $ping . "\n" .
                TF::WHITE . "Experience : " . TF::GREEN . $expLevel . "\n" .
                TF::WHITE . $coords . "\n" .
                TF::WHITE . "First Join : " . TF::GREEN . $firstPlayedDate;

                $form->setContent($content);
                $form->addButton(TF::BOLD . TF::RED . "EXIT", 0, "textures/blocks/barrier");
                $form->sendToPlayer($player);
            });
    }

    private function getRank(Player $player): ?string {
        $purePerms = $this->getPlugin("PurePerms");
        $ranksystem = $this->getPlugin("RankSystem");

        if ($purePerms !== null) {
            $group = $purePerms->getUserDataMgr()->getGroup($player);
            return $group !== null ? $group->getName() : null;
        } elseif ($ranksystem !== null) {
            $session = $ranksystem->getSessionManager()->get($player);
            $rankNames = [];

            foreach ($session->getRanks() as $rank) {
                $rankNames[] = $rank->getName();
            }

            return implode(', ', $rankNames);
        }

        return null;
    }

    private function getPlugin(string $name) {
        return \pocketmine\Server::getInstance()->getPluginManager()->getPlugin($name);
    }

    private function getFormattedFirstPlayedDate(Player $player): string {
        $timestamp = (int)($player->getFirstPlayed() / 1000);
        $formattedDate = date("F j, Y H:i:s", $timestamp);
        return $formattedDate . " WIB";
    }

    private function getCoords(Player $player): string {
        $x = (int) $player->getPosition()->getX();
        $y = (int) $player->getPosition()->getY();
        $z = (int) $player->getPosition()->getZ();

        $position = TF::WHITE . "Position : " . TF::GREEN . $x . " " . $y . " " . $z . TF::RESET;

        return $position;
    }

    private function getPing(Player $player): ?int {
        $ping = $player->getNetworkSession()->getPing();
        return $ping;
    }

    private function getWorldName(Player $player) {
        $worldName = $player->getWorld()->getProvider()->getWorldData()->getName();
        return $worldName;
    }
}
