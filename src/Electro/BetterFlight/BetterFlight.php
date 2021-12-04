<?php

namespace Electro\BetterFlight;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class BetterFlight extends PluginBase implements Listener{

    public array $worlds = [];
    public bool $whitelist = true;
    public bool $loseFlightOnJoin;
    public bool $loseFlightOnHit;
    public string $flightDisabledMsg;
    public string $flightEnabledMsg;
    public string $loseFlightMsg;
    public string $survivalOnlyMsg;
    public string $flightDisabledInWorldMsg;

    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->loseFlightOnJoin = $this->getConfig()->get("LoseFlightOnJoin");
        $this->loseFlightOnHit = $this->getConfig()->get("LoseFlightWhenHit");
        $this->loseFlightMsg = $this->getConfig()->get("LoseFlightMsg");
        $this->flightDisabledMsg = $this->getConfig()->get("FlightDisabledMsg");
        $this->flightEnabledMsg = $this->getConfig()->get("FlightEnabledMsg");
        $this->survivalOnlyMsg = $this->getConfig()->get("SurvivalOnlyMsg");
        $this->flightDisabledInWorldMsg = $this->getConfig()->get("FlightDisabledInWorldMsg");

        foreach ($this->getConfig()->get("Worlds") as $world)
        {
            $this->worlds[] = $world;
        }

        if ($this->getConfig()->get("Mode") !== "Whitelist" && !$this->getConfig()->get("Mode") !== "whitelist")
        {
            $this->whitelist = false;
        }
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        if (!$sender instanceof Player)
        {
            $sender->sendMessage("§cYou must be in-game to run this command");
            return true;
        }
        switch($cmd->getName()) {
            case "flight":
                if(!$sender instanceof Player) {
                    $sender->sendMessage("§l§cERROR: §r§aYou must be in-game to execute this command");
                    return true;
                }
                if (isset($args[0]) && !$this->getServer()->getPlayerByPrefix($args[0]) instanceof Player) {
                    $sender->sendMessage("§l§cERROR: §r§aYou have entered an invalid Player Username.");
                    return true;
                }

                $player = $sender;
                if (isset($args[0]))
                {
                    $player = $this->getServer()->getPlayerByPrefix($args[0]);
                }

                if ($player->isCreative())
                {
                    if (isset($args[0]))
                    {
                        $sender->sendMessage($this->survivalOnlyMsg);
                    }
                    else
                    {
                        $player->sendMessage($this->survivalOnlyMsg);
                    }
                    return true;
                }

                if ($this->whitelist && !in_array($player->getWorld()->getFolderName(), $this->worlds))
                {
                    $player->setAllowFlight(false);
                    $player->setFlying(false);
                    if (isset($args[0]) && $sender->getName() !== $player->getName())
                    {
                        $sender->sendMessage($this->flightDisabledInWorldMsg);
                    }
                    else
                    {
                        $player->sendMessage($this->flightDisabledInWorldMsg);
                    }
                    return true;
                }
                if (!$this->whitelist && in_array($player->getWorld()->getFolderName(), $this->worlds))
                {
                    $player->setAllowFlight(false);
                    $player->setFlying(false);
                    if (isset($args[0]) && $sender->getName() !== $player->getName())
                    {
                        $sender->sendMessage($this->flightDisabledInWorldMsg);
                    }
                    else
                    {
                        $player->sendMessage($this->flightDisabledInWorldMsg);
                    }
                    return true;
                }

                if ($player->getAllowFlight())
                {
                    $player->setAllowFlight(false);
                    $player->setFlying(false);
                    if (isset($args[0]) && $sender->getName() !== $player->getName())
                    {
                        $sender->sendMessage("§aYou have disabled " . $player->getName() . "'s flight");
                    }
                    else
                    {
                        $player->sendMessage($this->flightDisabledMsg);
                    }
                    return true;
                }
                $player->setAllowFlight(true);
                if (isset($args[0]) && $sender->getName() !== $player->getName())
                {
                    $sender->sendMessage("§aYou have enabled " . $player->getName() . "'s flight");
                }
                else
                {
                    $player->sendMessage($this->flightEnabledMsg);
                }
                break;
        }
        return true;
    }

    public function onDamage(EntityDamageEvent $event)
    {
        $player = $event->getEntity();

        if(!$event instanceof EntityDamageByEntityEvent) return;
        if (!$player instanceof Player) return;
        $damager = $event->getDamager();
        if (!$damager instanceof Player) return;
        if (!$this->loseFlightOnHit) return;
        if (!$player->isCreative())
        {
            if ($player->getAllowFlight())
            {
                $player->setAllowFlight(false);
                $player->setFlying(false);
                $player->sendMessage($this->loseFlightMsg);
            }
        }
        if (!$damager->isCreative())
        {
            if ($damager->getAllowFlight())
            {
                $damager->setAllowFlight(false);
                $player->setFlying(false);
                $damager->sendMessage($this->loseFlightMsg);
            }
        }
    }

    public function onLevelChange(EntityTeleportEvent $event)
    {
        $player = $event->getEntity();
        if (!$player instanceof Player) return;
        $world = $event->getTo()->getWorld();

        if ($this->whitelist && !in_array($world->getFolderName(), $this->worlds))
        {
            if ($player->getAllowFlight())
            {
                $player->setAllowFlight(false);
                $player->setFlying(false);
                $player->sendMessage($this->flightDisabledInWorldMsg);
            }
        }
        if (!$this->whitelist && in_array($world->getFolderName(), $this->worlds))
        {
            if ($player->getAllowFlight())
            {
                $player->setAllowFlight(false);
                $player->setFlying(false);
                $player->sendMessage($this->flightDisabledInWorldMsg);
            }
        }
    }
}
