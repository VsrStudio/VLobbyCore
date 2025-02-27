<?php

namespace LC\ui;

use pocketmine\command\Command;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as MG;

use Vecnavium\FormsUI\Form;
use Vecnavium\FormsUI\FormAPI;
use Vecnavium\FormsUI\SimpleForm;

use LC\LobbyCore;

class UI {

    public $plugin;

    public function __construct(){
        $this->plugin = LobbyCore::getInstance();
    }

    public function getGames(Player $player){
        $form = new SimpleForm(function(Player $player, int $data = null){
            if($data === null){
                return true;
            }
            switch($data){
                case 0:
                    $this->plugin->getServer()->getCommandMap()->dispatch($player, $this->plugin->getConfig()->get("CommandForm1"));
                    break;
                case 1:
                    $this->plugin->getServer()->getCommandMap()->dispatch($player, $this->plugin->getConfig()->get("CommandForm2"));
                    break;
                case 2:
                    $this->plugin->getServer()->getCommandMap()->dispatch($player, $this->plugin->getConfig()->get("CommandForm3"));
                    break;
                case 3:
                    $this->plugin->getServer()->getCommandMap()->dispatch($player, $this->plugin->getConfig()->get("CommandForm4"));
                    break;
                case 4:
                    $this->plugin->getServer()->getCommandMap()->dispatch($player, $this->plugin->getConfig()->get("CommandForm5"));
                    break;
                case 5:
                    $this->plugin->getServer()->getCommandMap()->dispatch($player, $this->plugin->getConfig()->get("CommandForm6"));
                    break;
            }
        });
        $form->setTitle(MG::RED . $this->plugin->getConfig()->get("GameTitle"));
        $form->setContent(MG::RED . $this->plugin->getConfig()->get("GameInfo"));
        $form->addButton(MG::RED . $this->plugin->getConfig()->get("GameForm1"));
        $form->addButton(MG::RED . $this->plugin->getConfig()->get("GameForm2"));
        $form->addButton(MG::RED . $this->plugin->getConfig()->get("GameForm3"));
        $form->addButton(MG::RED . $this->plugin->getConfig()->get("GameForm4"));
        $form->addButton(MG::RED . $this->plugin->getConfig()->get("GameForm5"));
        $form->addButton(MG::RED . $this->plugin->getConfig()->get("GameForm6"));
        $form->addButton("§0Black");
        $form->sendToPlayer($player);
    }

    public function getCosmetics(Player $player){
        $form = new SimpleForm(function(Player $player, int $data = null){
            if($data === null){
                return true;
            }
            switch($data){
                case 0:
                    if (!$player->hasPermission("lobbycore.use.fly")) {
                        $player->sendMessage("You not have permissions to use this command");
                    } else {
                        $this->FlyForm($player);
                    }
                break;
                case 1:
                    if (!$player->hasPermission("lobbycore.use.size")) {
                        $player->sendMessage("You not have permissions to use this command");
                    } else {
                        $this->SizeForm($player);
                    }
                break;
                case 2;
                    $this->plugin->getServer()->getCommandMap()->dispatch($player, "nick");
                break;
                case 3;
                    $this->plugin->getServer()->getCommandMap()->dispatch($player, "cape");
                break;
                case 4;

                break;
            }
        });
        $form->setTitle("§bCosmetics");
        $form->setContent("§fPick THe Setting!");
        $form->addButton("§aFly");
        $form->addButton("§cSize");
        $form->addButton("§6Nick");
        $form->addButton("§bCape");
        $form->addButton("§0Black");
        $form->sendToPlayer($player);
    }

    public function FlyForm(Player $player){
        $form = new SimpleForm(function(Player $player, int $data = null){
            if($data === null){
                return true;
            }
            switch($data){
                case 0:
                    $player->setFlying(true);
                    $player->setAllowFlight(true);
                    $player->sendMessage("§aFly ON!");
                    break;
                case 1:
                    $player->setFlying(false);
                    $player->setAllowFlight(false);
                    $player->sendMessage("§cFly OFF!");
                    break;
            }
        });
        $form->setTitle(MG::BLUE . $this->plugin->getConfig()->get("FlyTitle"));
        $form->setContent(MG::GRAY . $this->plugin->getConfig()->get("FlyInfo"));
        $form->addButton(MG::GREEN . $this->plugin->getConfig()->get("FlyForm1"));
        $form->addButton(MG::RED . $this->plugin->getConfig()->get("FlyForm2"));
        $form->addButton("§0Black");
        $form->sendToPlayer($player);
    }

    public function SizeForm(Player $player){
        $form = new SimpleForm(function(Player $player, int $data = null){
            if($data === null){
                return true;
            }
            switch($data){
                case 0:
                    $player->setScale("1.0");
                    $player->sendMessage(MG::GREEN . $this->plugin->getConfig()->get("SizeMessageNormal"));
                    break;
                case 1:
                    $player->setScale("1.5");
                    $player->sendMessage(MG::GREEN . $this->plugin->getConfig()->get("SizeMessageMedium"));
                    break;
                case 2:
                    $player->setScale("2.0");
                    $player->sendMessage(MG::GREEN . $this->plugin->getConfig()->get("SizeMessageBig"));
                    break;
            }
        });
        $form->setTitle(MG::BLUE . $this->plugin->getConfig()->get("SizeTitle"));
        $form->setContent(MG::GRAY . $this->plugin->getConfig()->get("SizeInfo"));
        $form->addButton(MG::GREEN . $this->plugin->getConfig()->get("SizeForm1"));
        $form->addButton(MG::GREEN . $this->plugin->getConfig()->get("SizeForm2"));
        $form->addButton(MG::GREEN . $this->plugin->getConfig()->get("SizeForm3"));
        $form->addButton("§0Black");
        $form->sendToPlayer($player);
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
        return $this->getServer()->getPluginManager()->getPlugin($name);
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
