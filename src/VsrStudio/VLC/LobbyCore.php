<?php

namespace VsrStudio\VLC;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as MG;

use VsrStudio\VLC\event\EventListener;
use VsrStudio\VLC\event\Protection;

use VsrStudio\VLC\commands\HubCommand;
use VsrStudio\VLC\commands\ItemCommand;

use VsrStudio\VLC\ui\UI;

class LobbyCore extends PluginBase implements Listener {

    private static $instance;
    private static UI $ui;
	
	public function onLoad() : void {
		self::$instance = $this;
	}

    public function onEnable(): void {
        $this->saveDefaultConfig();
        self::$instance = $this;
        self::$ui = new UI($this);
        $this->getLogger()->info(MG::GREEN . "Ventiy Network LobbyCore enabled successfully, plugin made by VsrSrudio");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new Protection(), $this);
        $this->getServer()->getCommandMap()->register("hub", new HubCommand());
        $this->saveResource("config.yml");
    }

    public function onDisable(): void {

    }

    public static function getInstance() : LobbyCore {
        return self::$instance;
    }
    
    public static function getUI(): UI {
        return self::$ui;
    }
}
