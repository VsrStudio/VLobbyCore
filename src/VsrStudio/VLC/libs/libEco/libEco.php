<?php

declare(strict_types=1);

namespace VsrStudio\VLC\libs\libEco;

use Closure;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use VsrStudio\VLC\libs\libEco\Utils\Utils;
use onebone\economyapi\EconomyAPI;
use pocketmine\player\Player;
use pocketmine\Server as PMServer;

final class libEco
{
    /**
     * @return array<string, object|null>
     */
    private static function getEconomy(): array
    {
        $economies = [];
        $economyAPI = PMServer::getInstance()
            ->getPluginManager()
            ->getPlugin("EconomyAPI");
        if ($economyAPI !== null) {
            $economies[Utils::ECONOMYAPI] = $economyAPI;
        }

        $bedrockEconomy = PMServer::getInstance()
            ->getPluginManager()
            ->getPlugin("BedrockEconomy");
        if ($bedrockEconomy !== null) {
            $economies[
                Utils::BEDROCKECONOMYAPI
            ] = BedrockEconomyAPI::getInstance();
        }

        return $economies;
    }

    public function isInstall(): bool
    {
        return !empty(self::getEconomy());
    }

    public static function myMoney(Player $player, Closure $callback): void
    {
        $economies = self::getEconomy();

        $balance = 0;
        $callbacksRemaining = count($economies);

        if ($callbacksRemaining === 0) {
            $callback($balance);
            return;
        }

        $closure = function ($amount) use (
            &$balance,
            &$callbacksRemaining,
            $callback
        ) {
            $balance += $amount;
            $callbacksRemaining--;

            if ($callbacksRemaining === 0) {
                $callback($balance);
            }
        };

        foreach ($economies as $type => $economy) {
            if ($type === Utils::ECONOMYAPI) {
                $money = $economy->myMoney($player);
                assert(is_float($money));
                $closure($money);
            } elseif ($type === Utils::BEDROCKECONOMYAPI) {
                $economy->getPlayerBalance(
                    $player->getName(),
                    ClosureContext::create(static function (?int $balance) use (
                        $closure
                    ): void {
                        $closure($balance ?? 0);
                    })
                );
            }
        }
    }

    public static function addMoney(Player $player, int $amount): void
    {
        $economies = self::getEconomy();

        foreach ($economies as $type => $economy) {
            if ($type === Utils::ECONOMYAPI) {
                $economy->addMoney($player, $amount);
            } elseif ($type === Utils::BEDROCKECONOMYAPI) {
                $economy->addToPlayerBalance($player->getName(), $amount);
            }
        }
    }

    public static function reduceMoney(
        Player $player,
        int $amount,
        Closure $callback
    ): void {
        $economies = self::getEconomy();

        $success = true;
        $callbacksRemaining = count($economies);

        if ($callbacksRemaining === 0) {
            $callback($success);
            return;
        }

        $closure = function ($result) use (
            &$success,
            &$callbacksRemaining,
            $callback
        ) {
            if (!$result) {
                $success = false;
            }
            $callbacksRemaining--;

            if ($callbacksRemaining === 0) {
                $callback($success);
            }
        };

        foreach ($economies as $type => $economy) {
            if ($type === Utils::ECONOMYAPI) {
                $result =
                    $economy->reduceMoney($player, $amount) ===
                    EconomyAPI::RET_SUCCESS;
                $closure($result);
            } elseif ($type === Utils::BEDROCKECONOMYAPI) {
                $economy->subtractFromPlayerBalance(
                    $player->getName(),
                    (int) ceil($amount),
                    ClosureContext::create(static function (bool $success) use (
                        $closure
                    ): void {
                        $closure($success);
                    })
                );
            }
        }
    }
}
