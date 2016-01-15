<?php

/*
 *
 *  _                       _           _ __  __ _             
 * (_)                     (_)         | |  \/  (_)            
 *  _ _ __ ___   __ _  __ _ _  ___ __ _| | \  / |_ _ __   ___  
 * | | '_ ` _ \ / _` |/ _` | |/ __/ _` | | |\/| | | '_ \ / _ \ 
 * | | | | | | | (_| | (_| | | (_| (_| | | |  | | | | | |  __/ 
 * |_|_| |_| |_|\__,_|\__, |_|\___\__,_|_|_|  |_|_|_| |_|\___| 
 *                     __/ |                                   
 *                    |___/                                                                     
 * 
 * This program is a third party build by ImagicalMine.
 * 
 * PocketMine is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author ImagicalMine Team
 * @link http://forums.imagicalcorp.ml/
 * 
 *
*/

namespace pocketmine\inventory;

use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\item\Item;
use pocketmine\Server;

class CraftingTransactionGroup extends SimpleTransactionGroup{
	/** @var Item[] */
	protected $input = [];
	/** @var Item[] */
	protected $output = [];

	/** @var Recipe */
	protected $recipe = null;

	public function __construct(SimpleTransactionGroup $group){
		parent::__construct();
		$this->transactions = $group->getTransactions();
		$this->inventories = $group->getInventories();
		$this->source = $group->getSource();

		$this->matchItems($this->output, $this->input);
	}

	public function addTransaction(Transaction $transaction){
		parent::addTransaction($transaction);
		$this->input = [];
		$this->output = [];
		$this->matchItems($this->output, $this->input);
	}

	/**
	 * Gets the Items that have been used
	 *
	 * @return Item[]
	 */
	public function getRecipe(){
		return $this->input;
	}

	/**
	 * @return Item
	 */
	public function getResult(){
		reset($this->output);

		return current($this->output);
	}

	public function canExecute(){
		if(count($this->output) !== 1 or count($this->input) === 0){
			return false;
		}

		return $this->getMatchingRecipe() instanceof Recipe;
	}

	/**
	 * @return Recipe
	 */
	public function getMatchingRecipe(){
		if($this->recipe === null){
			$this->recipe = Server::getInstance()->getCraftingManager()->matchTransaction($this);
		}

		return $this->recipe;
	}

	public function execute(){
		if($this->hasExecuted() or !$this->canExecute()){
			return false;
		}

		Server::getInstance()->getPluginManager()->callEvent($ev = new CraftItemEvent($this, $this->getMatchingRecipe()));
		if($ev->isCancelled()){
			foreach($this->inventories as $inventory){
				$inventory->sendContents($inventory->getViewers());
			}

			return false;
		}

		foreach($this->transactions as $transaction){
			$transaction->getInventory()->setContents($transaction->getViewers()->getSlot(), $transaction->getTargetItem(), $transaction->getSourceItem());
		}
		$this->hasExecuted = true;

		return true;
	}
}
