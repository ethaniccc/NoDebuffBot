<?php

declare(strict_types=1);

namespace ethaniccc\NoDebuffBot;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener{

    /** @var array */
    private $fighting = [];

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDeath(PlayerDeathEvent $event) : void{
        if(isset($this->fighting[$event->getPlayer()->getName()])){
            $event->setDrops([]);
            $e = $event->getPlayer()->getLastDamageCause();
            if($e instanceof EntityDamageByEntityEvent){
                if($e->getDamager() instanceof Bot){
                    $this->getServer()->broadcastMessage(TextFormat::RED . "{$event->getPlayer()->getName()} lost to the NoDebuff bot. The bot had {$e->getDamager()->getRemainingPots()} pots remaining.");
                }
            }
        }
    }

    public function onDamage(EntityDamageEvent $event) : void{
        if($event->getCause() === EntityDamageEvent::CAUSE_FALL){
            $event->setCancelled();
        }
    }

    public function onExhaust(PlayerExhaustEvent $event) : void{
        if(isset($this->fighting[$event->getPlayer()->getName()])){
            $event->setCancelled();
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        switch($command->getName()){
            case "nodebuff":
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "You can only run this in-game!");
                } else {
                    $nbt = Entity::createBaseNBT($sender->asVector3()->subtract(10, 0, 10));
                    $nbt->setTag($sender->namedtag->getTag("Skin"));
                    $bot = new Bot($sender->getLevel(), $nbt, $sender->getName());
                    $bot->setNameTagAlwaysVisible(true);
                    $bot->spawnToAll();
                    $bot->giveItems();
                    $bot->setCanSaveWithChunk(false);
                    for($i = 0; $i <= 35; ++$i){
                        $sender->getInventory()->setItem($i, Item::get(Item::SPLASH_POTION, 22, 1));
                    }
                    $effect = new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 100000, 255);
                    $sender->addEffect($effect);
                    $bot->addEffect($effect);
                    $sword = Item::get(Item::DIAMOND_SWORD);
                    $sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::FIRE_ASPECT)));
                    $sender->getInventory()->setItem(0, $sword);
                    $sender->getInventory()->setItem(1, Item::get(Item::ENDER_PEARL, 0, 16));
                    $sender->getArmorInventory()->setHelmet(Item::get(Item::DIAMOND_HELMET));
                    $sender->getArmorInventory()->setChestplate(Item::get(Item::DIAMOND_CHESTPLATE));
                    $sender->getArmorInventory()->setLeggings(Item::get(Item::DIAMOND_LEGGINGS));
                    $sender->getArmorInventory()->setBoots(Item::get(Item::DIAMOND_BOOTS));
                    $sender->getInventory()->setHeldItemIndex(0);
                    $this->fighting[$sender->getName()] = 0;
                }
                break;
        }
        return true;
    }

}
