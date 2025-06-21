<?php

namespace VsrStudio\VLC\event;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\Listener;
use VsrStudio\VLC\LobbyCore;

class Protection implements Listener {

    public static bool $protectionEnabled = true;

    public static function toggleProtection(): bool {
        self::$protectionEnabled = !self::$protectionEnabled;
        return self::$protectionEnabled;
    }

    public function onDamage(\pocketmine\event\entity\EntityDamageEvent $ev): void {
        if (!self::$protectionEnabled) return;
        $pl = $ev->getEntity();
        if ($pl instanceof Player && $pl->getWorld() === Server::getInstance()->getWorldManager()->getDefaultWorld()) {
            $ev->cancel();
        }
    }

    public function onExplosion(\pocketmine\event\entity\EntityExplodeEvent $ev): void {
        if (self::$protectionEnabled) {
            $ev->cancel();
        }
    }

    public function onBreak(\pocketmine\event\block\BlockBreakEvent $ev): void {
        if (!self::$protectionEnabled) return;
        $pl = $ev->getPlayer();
        if ($pl->getWorld() === Server::getInstance()->getWorldManager()->getDefaultWorld()) {
            $ev->cancel();
            $pl->sendMessage("§c[ ! ] You can't destroy blocks");
        }
    }

    public function onPlace(\pocketmine\event\block\BlockPlaceEvent $ev): void {
        if (!self::$protectionEnabled) return;
        $pl = $ev->getPlayer();
        if ($pl->getWorld() === Server::getInstance()->getWorldManager()->getDefaultWorld()) {
            $ev->cancel();
            $pl->sendMessage("§c[ ! ] You can't place blocks");
        }
    }

    public function onDrop(\pocketmine\event\player\PlayerDropItemEvent $ev): void {
        if (!self::$protectionEnabled) return;
        $pl = $ev->getPlayer();
        if ($pl->getWorld() === Server::getInstance()->getWorldManager()->getDefaultWorld()) {
            $ev->cancel();
        }
    }

    public function onExhaust(\pocketmine\event\player\PlayerExhaustEvent $ev): void {
        if (!self::$protectionEnabled) return;
        $pl = $ev->getPlayer();
        if ($pl->getWorld() === Server::getInstance()->getWorldManager()->getDefaultWorld()) {
            $ev->cancel();
        }
    }
}
