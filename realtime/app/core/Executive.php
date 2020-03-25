<?php

namespace Realtime;

class Executive {
	// Object storage.
	private $binance;
	private $scavengers = [];

	// Symbols.
	private $symbols = [
		"VIBBTC"
	];

	public function __construct() {
		// Create Binance connection.
		$this->binance = new \Binance\API(API["Key"], API["Secret"]);
		$this->binance->useServerTime();

		Writer::write("debugg/scavenger.txt", "Executive", "Establishing Binance API connection");

		// Create, load and store scavengers.
		$this->scavengers();

		// Initize the process.
		$this->process();
	}

	// Create Scavenger object for each symbol.
	private function scavengers() {
		foreach ($this->symbols as $symbol) {
			// Create scavenger objects.
			$this->scavengers[$symbol] = new Scavenger($symbol);
		}
	}

	private function process() {
		$ticks = 0;

		// Update prices.
		$this->binance->ticker(false, function($api, $symbol, $ticker) {
			$prices = $ticker;
		});

		// Create the first timeblock to initiate the process.
		foreach ($this->symbols as $symbol) {
			$timeblocks[$symbol] = new Timeblock();
			$timeblocks[$symbol]->btc = $prices["BTCUSDT"];
			Writer::write("debugg/scavenger.txt", $symbol, "Creating first timeblock");
		}

		// Infinite loop.
		while (1) {
			$ticks++;

			// Set new symbol prices.
			$prices = $this->binance->prices();

			// Inject new price into a timeblock for each symbol.
			foreach ($timeblocks as $symbol => $timeblock) {
				// Check for empty price.
				if (isset($prices[$symbol])) {
					$timeblock->injectPrice($prices[$symbol]);
				} else {
					Writer::write("debugg/scavenger.txt", "Executive", "Empty price: Initialize replaceable tick");

					$ticks--;
				} 
			}

			// Store all symbol prices.
			if ($ticks % TimeblockSize == false) {
				foreach ($this->scavengers as $key => $scavenger) {
					// Check if timeblock has any prices.

					// Inject timeblock.
					$scavenger->injectTimeblock($timeblocks[$key]);
					$scavenger->tick();

					// Check for transaction request.
					/*if ($tick = $scavenger->tick()) {
						$order = $this->binance->{$tick["type"]}($key, $tick["quantity"], $tick["price"]);
					}*/
				}

				// Create new timeblocks.
				foreach ($this->symbols as $symbol) {
					$timeblocks[$symbol] = new Timeblock();
					$timeblocks[$symbol]->btc = $prices["BTCUSDT"];

					Writer::write("debugg/scavenger.txt", "Executive", "New timeblock created");
				}

				// Reset ticks.
				$ticks = 0;
			}
		}
	}
}