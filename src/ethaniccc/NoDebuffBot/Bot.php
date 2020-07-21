<?php

namespace ethaniccc\NoDebuffBot;

use pocketmine\block\Liquid;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\Sword;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class Bot extends Human{

    /** @var string */
    private $target = "";

    /** @var int */
    private $hitTick;

    /** @var int */
    private $neededPots = 0;

    /** @var int */
    private $potTick = 0;

    /** @var float */
    private $speed = 0.4;

    /** @var int */
    private $potionsRemaining = 34;

    /**
     * Bot constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     * @param string $target
     * @param Skin $skin
     */
    public function __construct(Level $level, CompoundTag $nbt, string $target){
        parent::__construct($level, $nbt);
        $this->target = $target;
    }

    /**
     * @return Player|null
     */
    public function getTargetPlayer() : ?Player{
        return Server::getInstance()->getPlayer($this->target);
    }

    public function giveItems() : void{
        for($i = 0; $i <= 35; ++$i){
            $this->getInventory()->setItem($i, Item::get(Item::SPLASH_POTION, 22, 1));
        }
        $this->getInventory()->setItem(0, Item::get(Item::DIAMOND_SWORD));
        $this->getArmorInventory()->setHelmet(Item::get(Item::DIAMOND_HELMET));
        $this->getArmorInventory()->setChestplate(Item::get(Item::DIAMOND_CHESTPLATE));
        $this->getArmorInventory()->setLeggings(Item::get(Item::DIAMOND_LEGGINGS));
        $this->getArmorInventory()->setBoots(Item::get(Item::DIAMOND_BOOTS));
        $this->getInventory()->setHeldItemIndex(0);
    }

    /**
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);
        if(!$this->isAlive() || $this->getTargetPlayer() === null || !$this->getTargetPlayer()->isAlive()){
            if(!$this->closed) $this->flagForDespawn();
            return false;
        }
        $roundedHealth = round($this->getHealth(), 0);
        $this->setNameTag(TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "NoDebuff " . TextFormat::WHITE . "|| " . TextFormat::RED . "$roundedHealth");
        $position = $this->getTargetPlayer()->asVector3();
        $x = $position->x - $this->getX();
        $z = $position->z - $this->getZ();
        if($x != 0 || $z != 0){
            $this->motion->x = $this->getSpeed() * 0.35 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.35 * ($z / (abs($x) + abs($z)));
        }
        $this->setSprinting(true);
        if($this->getHealth() < 5){
            if($this->potionsRemaining !== 0){
                if($this->yaw < 0){
                    $this->yaw = abs($this->yaw);
                } elseif($this->yaw == 0){
                    $this->yaw = -180;
                } else {
                    $this->yaw = -$this->yaw;
                }
                $this->pitch = -85;
                $this->getInventory()->setHeldItemIndex(1);
                ++$this->neededPots;
                $player = $this->getTargetPlayer();
                $soundPacket = new LevelSoundEventPacket();
                $soundPacket->sound = LevelSoundEventPacket::SOUND_GLASS;
                $soundPacket->position = $this->asVector3();
                $player->dataPacket($soundPacket);
                $effect = new EffectInstance(Effect::getEffect(Effect::INSTANT_HEALTH), 0, 1);
                $this->addEffect($effect);
                --$this->potionsRemaining;
                $this->potTick = Server::getInstance()->getTick();
            } else {
                $this->lookAt($this->getTargetPlayer()->asVector3());
                if($this->distance($this->getTargetPlayer()) <= 3){
                    $this->getInventory()->setHeldItemIndex(0);
                    if(mt_rand(0, 100) % 4 && Server::getInstance()->getTick() - $this->potTick >= 20){
                        $event = new EntityDamageByEntityEvent($this, $this->getTargetPlayer(), EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getInventory()->getItemInHand() instanceof Sword ? $this->getInventory()->getItemInHand()->getAttackPoints() : 0.5);
                        $this->broadcastEntityEvent(4);
                        $this->getTargetPlayer()->attack($event);
                    }
                }
            }
        } else {
            if(!$this->recentlyHit()){
                $this->move($this->motion->x, $this->motion->y, $this->motion->z);
            }
            if($this->getTargetPlayer() === null){
                $this->flagForDespawn();
                return false;
            } elseif($this->neededPots === 1){
                if($this->potionsRemaining !== 0){
                    if($this->yaw < 0){
                        $this->yaw = abs($this->yaw);
                    } elseif($this->yaw == 0){
                        $this->yaw = -180;
                    } else {
                        $this->yaw = -$this->yaw;
                    }
                    $this->pitch = -85;
                    $this->getInventory()->setHeldItemIndex(1);
                    ++$this->neededPots;
                    $player = $this->getTargetPlayer();
                    $soundPacket = new LevelSoundEventPacket();
                    $soundPacket->sound = LevelSoundEventPacket::SOUND_GLASS;
                    $soundPacket->position = $this->asVector3();
                    $player->dataPacket($soundPacket);
                    $effect = new EffectInstance(Effect::getEffect(Effect::INSTANT_HEALTH), 0, 1);
                    $this->addEffect($effect);
                    --$this->potionsRemaining;
                    $this->potTick = Server::getInstance()->getTick();
                } else {
                    $this->lookAt($this->getTargetPlayer()->asVector3());
                    if($this->distance($this->getTargetPlayer()) <= 3){
                        $this->getInventory()->setHeldItemIndex(0);
                        if(mt_rand(0, 100) % 4 && Server::getInstance()->getTick() - $this->potTick >= 20){
                            $event = new EntityDamageByEntityEvent($this, $this->getTargetPlayer(), EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getInventory()->getItemInHand() instanceof Sword ? $this->getInventory()->getItemInHand()->getAttackPoints() : 0.5);
                            $this->broadcastEntityEvent(4);
                            $this->getTargetPlayer()->attack($event);
                        }
                    }
                }
            } else {
                $this->lookAt($this->getTargetPlayer()->asVector3());
                if($this->distance($this->getTargetPlayer()) <= 3){
                    $this->getInventory()->setHeldItemIndex(0);
                    if(mt_rand(0, 100) % 4 && Server::getInstance()->getTick() - $this->potTick >= 20){
                        $event = new EntityDamageByEntityEvent($this, $this->getTargetPlayer(), EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getInventory()->getItemInHand() instanceof Sword ? $this->getInventory()->getItemInHand()->getAttackPoints() : 0.5);
                        $this->broadcastEntityEvent(4);
                        $this->getTargetPlayer()->attack($event);
                    }
                }
            }
        }
        return $this->isAlive();
    }

    /**
     * @param Entity $attacker
     * @param float $damage
     * @param float $x
     * @param float $z
     * @param float $base
     */
    public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4) : void{
        parent::knockBack($attacker, $damage, $x, $z, $base);
        $this->hitTick = Server::getInstance()->getTick();
    }

    /**
     * @return float
     */
    public function getSpeed() : float{
        return $this->speed;
    }

    public function attack(EntityDamageEvent $source): void{
        parent::attack($source);
        $this->hitTick = Server::getInstance()->getTick();
    }

    public function recentlyHit() : bool{
        return $this->hitTick !== null ? Server::getInstance()->getTick() - $this->hitTick <= 4 : false;
    }

    public function getRemainingPots() : int{
        return $this->potionsRemaining;
    }

}