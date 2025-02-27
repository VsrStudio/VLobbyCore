<?php
declare(strict_types=1);

namespace LC\ui;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

class FriendSystem {

    private string $dataFolder;

    public function __construct(string $dataFolder) {
        $this->dataFolder = $dataFolder;
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        $playerFile = new Config($this->dataFolder . $name . ".yml", Config::YAML);
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

        $playerFile = new Config($this->dataFolder . $name . ".yml", Config::YAML);
        $friends = $playerFile->get("Friend", []);

        foreach ($friends as $friend) {
            $friendPlayer = $this->getServer()->getPlayerExact($friend);
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
        $form->addButton("Manage Friends\nYou have " . $friendCount . " friends");    
        $form->addButton("Add Friend");    
        $form->addButton("Friend Requests (" . $requestCount . ")");    
        $form->addButton("Close");    
    
        $player->sendForm($form);    
    }    

    private function getFriendCount(Player $player): int {    
        $playerFile = new Config($this->dataFolder . $player->getName() . ".yml", Config::YAML);    
        return count($playerFile->get("Friend", []));    
    }    

    private function getFriendRequestCount(Player $player): int {    
        $playerFile = new Config($this->dataFolder . $player->getName() . ".yml", Config::YAML);    
        return count($playerFile->get("Invitations", []));    
    }    

    private function openFriendList(Player $player): void {    
        $playerFile = new Config($this->dataFolder . $player->getName() . ".yml", Config::YAML);    
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
                $form->addButton($friend . "\nClick to remove");    
            }    
        }    
        $player->sendForm($form);    
    }    

    private function openFriendRequests(Player $player): void {    
        $playerFile = new Config($this->dataFolder . $player->getName() . ".yml", Config::YAML);    
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
                $form->addButton("Accept from " . $request);    
            }    
        }    
        $player->sendForm($form);    
    }    

    private function removeFriend(Player $player, string $friend): void {
        $playerFile = new Config($this->dataFolder . $player->getName() . ".yml", Config::YAML);
        $friends = $playerFile->get("Friend", []);

        if (!in_array($friend, $friends)) {
            $player->sendMessage("You are not friends with $friend.");
            return;
        }

        unset($friends[array_search($friend, $friends)]);
        $playerFile->set("Friend", array_values($friends));
        $playerFile->save();

        $player->sendMessage("You removed $friend from your friend list.");
    }
}
