<?php

namespace PrefixQueue;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;

class PrefixQueue extends PluginBase implements Listener{
	/** @var array[][] */
	private $prefixes = [];
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onDisable(){
		foreach($this->prefixes as $pluginPrefixes){
			foreach($pluginPrefixes as $prefix){
				$callback = $prefix["onDisable"];
				if(is_callable($callback)){
					call_User_func($callback, $this);
				}
			}
		}
	}
	public static function register(Plugin $owner, callable $prefix, $priority = 50, callable $disableCallback = null){
		/** @var PrefixQueue $me */
		$me = $owner->getServer()->getPluginManager()->getPlugin("PrefixQueue");
		$me->registerPrefix($owner, $prefix, $priority, $disableCallback);
	}
	public function registerPrefix(Plugin $owner, callable $prefix, $priority = 50, callable $disableCallback = null){
		$prefix = ["prefix" => $prefix, "priority" => $priority, "onDisable" => $disableCallback, "owner" => new \WeakRef($owner)];
		if(!isset($this->prefixes[$name = $owner->getDescription()->getName()])){
			$this->prefixes[$name] = [];
		}
		$this->prefixes[$name][] = $prefix;
	}
	public function onPluginDisabled(PluginDisableEvent $event){
		if(isset($this->prefixes[$name = $event->getPlugin()->getDescription()->getName()])){
			unset($this->prefixes[$name]);
		}
	}
	/**
	 * @param PlayerChatEvent $event
	 * @priority HIGHEST
	 */
	public function onChat(PlayerChatEvent $event){
		$prefixes = $this->getPrefixes($event->getPlayer(), $event->getMessage());
		$prefixStr = "";
		foreach($prefixes as $priorityPrefixes){
			$prefixStr .= implode($priorityPrefixes);
		}
		$event->setFormat("$prefixStr " . $event->getFormat());
	}
	public function getPrefixes(Player $player, $message = null){
		$result = [];
		foreach($this->prefixes as $plugin => $prefixes){
			if(count($prefixes) === 0){
				continue;
			}
			/** @var \WeakRef $ref */
			$ref = array_rand($prefixes)["owner"];
			if($ref->valid()){
				/** @var Plugin $instance */
				$instance = $ref->get();
				if($instance->isEnabled()){
					foreach($prefixes as $prefix){
						$priority = $prefix["priority"];
						$string = call_user_func($prefix["prefix"], $player, $message);
						if(!isset($result[$priority])){
							$result[$priority] = [];
						}
						$result[$priority][] = $string;
					}
				}
			}
		}
		krsort($result, SORT_NUMERIC);
		return $result;
	}
}