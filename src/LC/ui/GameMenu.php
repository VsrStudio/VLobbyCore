<?php
declare(strict_types=1);

namespace LC\event;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\network\mcpe\protocol\TransferPacket;

class GameMenu {

    private Config $config;

    public function __construct() {
        $this->config = new Config(Server::getInstance()->getDataPath() . "/plugins/LobbyCore/config.yml", Config::YAML);
    }

    public function games(Player $player): void {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) return;

            $games = $this->config->get("games", []);
            if (!isset($games[$data])) return;

            $gameConfig = $games[$data];

            if ($gameConfig["type"] === "world") {
                // Jika tipe "world", hitung jumlah pemain dan teleport
                $totalPlayers = $this->getWorldPlayerCount($gameConfig["worlds"] ?? []);
                $player->sendMessage("§l§c[Game] > §r§cSedang Mentransfer Ke " . ucfirst($data) . "!");
                $player->sendMessage("§l§c[Game] > §r§bPemain di dalam: $totalPlayers");
                
                if (isset($gameConfig["command"])) {
                    Server::getInstance()->dispatchCommand($player, $gameConfig["command"]);
                }
            } elseif ($gameConfig["type"] === "server") {
                // Jika tipe "server", hitung jumlah pemain dan transfer ke server lain
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
                // Hitung jumlah pemain dalam dunia lokal
                $totalPlayers = $this->getWorldPlayerCount($info["worlds"] ?? []);
            } elseif ($info["type"] === "server") {
                // Hitung jumlah pemain dalam server lain
                if (isset($info["ip"]) && isset($info["port"])) {
                    $totalPlayers = $this->pingServer($info["ip"], (int)$info["port"]);
                }
            }

            if ($totalPlayers > 0) {
                $status = "$totalPlayers §r§bPlayers§r";
            }

            $buttonText = "§l§6" . ucfirst($game) . "\n" . $status;
            $form->addButton($buttonText, 0, "textures/ui/update_world_chunks", $game);
        }

        $player->sendForm($form);
    }

    private function transferToServer(Player $player, string $ip, int $port): void {
        $pk = new TransferPacket();
        $pk->address = $ip;
        $pk->port = $port;
        $player->getNetworkSession()->sendDataPacket($pk);
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

    private function pingServer(string $ip, int $port): int {
        $socket = @fsockopen("udp://$ip", $port, $errno, $errstr, 1);
        if (!$socket) return -1;

        stream_set_timeout($socket, 1);
        fwrite($socket, "\xFE\xFD\x09\x10\x20\x30\x40");
        $response = fread($socket, 2048);
        fclose($socket);

        if ($response !== false) {
            $data = explode("\x00", substr($response, 5));
            return (int)($data[1] ?? -1);
        }

        return -1;
    }
}
