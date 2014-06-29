<?php

namespace pemapmodder\worldeditart\utils\subcommand;

use pemapmodder\worldeditart\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\utils\TextFormat;

class SubcommandMap extends Command implements PluginIdentifiableCommand{
	/** @var Main */
	private $main;
	/** @var Subcommand[] */
	private $subcmds = [];
	/**
	 * @param string $name
	 * @param Main $main
	 * @param string $desc
	 * @param string $mainPerm
	 * @param string[]|string $aliases
	 */
	public function __construct($name, Main $main, $desc, $mainPerm, $aliases = []){
		$this->main = $main;
		parent::__construct($name, $desc, null, (array) $aliases);
		$this->setPermission($mainPerm);
	}
	public function registerSubcommand(Subcommand $subcmd){
		$this->subcmds[strtolower(trim($subcmd->getName()))] = $subcmd;
	}
	public function getPlugin(){
		return $this->main;
	}
	public function execute(CommandSender $issuer, $lbl, array $args){
		if(count($args) === 0){
			$args = ["help"];
		}
		$cmd = array_shift($args);
		if(isset($this->subcmds[$cmd = strtolower(trim($cmd))]) and $cmd !== "help"){
			if($this->subcmds[$cmd]->hasPermission($issuer)){
				try{
					$this->subcmds[$cmd]->run($args, $issuer);
				}
				catch(\Exception $exception){
					$issuer->sendMessage("Uh-oh. Something went wrong! An exception has been caught during executing your command.");
					$issuer->sendMessage("Error caught: ".($class = array_slice(explode("\\", get_class($exception)), -1)[0]));
					$issuer->sendMessage("Error message: ".$exception->getMessage());
					$issuer->sendMessage("The error has been reported to console.");
					$this->main->getLogger()->notice("An exception has been caught. Exception name: '$class'. Exception message: ".$exception->getMessage());
				}
			}
			else{
				$issuer->sendMessage("You don't have permission to do this!");
			}
		}else{
			$help = $this->getFullHelp($issuer);
			$page = 1;
			$max = (int) ceil(count($help) / 5);
			if(isset($args[0])){
				$page = max(1, (int) $args[0]);
				$page = min($max, $page);
			}
			$output = "Showing help page $page of $max\n";
			for($i = $page * 5; $i < ($page + 1) * 5 and isset($help[$i]); $i++){
				$output .= $help[$i] . "\n";
			}
			$issuer->sendMessage($output);
		}
		return true;
	}
	/**
	 * @param CommandSender $sender
	 * @return array
	 */
	public function getFullHelp(CommandSender $sender){
		$out = [];
		foreach($this->subcmds as $cmd){
			if(!$cmd->hasPermission($sender)){
				continue;
			}
			$output = "";
			$output .= "/{$this->getName()} ";
			$output .= TextFormat::LIGHT_PURPLE . $cmd->getName() . " ";
			$output .= TextFormat::GREEN . $cmd->getUsage() . " ";
			$output .= TextFormat::AQUA . $cmd->getDescription();
			$out[] = $output;
		}
		return $out;
	}
	/**
	 * @param Subcommand[] $subcmds
	 */
	public function registerAll(array $subcmds){
		foreach($subcmds as $subcmd){
			$this->registerSubcommand($subcmd);
		}
	}
}
