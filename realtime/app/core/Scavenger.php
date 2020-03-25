<?php

namespace Realtime;

class Scavenger {
	// Index.
	private $symbol;

	// Settings.
	private $entryPoint;
	private $exitPoint;
	private $quantity;

	// Measure points.
	private $lowPoint;
	private $highPoint;
	private $buyPoint;

	// Mode.
	private $mode = "buy";

	// Timeblocks storage.
	private $timeblocks = [];

	public function __construct($symbol) {
		// Set scavenger config.
		$this->loadConfig($symbol);
		$this->symbol = $symbol;

		Writer::write("debugg/scavenger.txt", $this->symbol, "Initializing Scavenger algorithm");
		Writer::write("debugg/scavenger.txt", $this->symbol, "Current transaction mode: " . $this->mode);
	}

	public function tick() {
		switch ($this->mode) {
			case "buy":
				return $this->buy();
				break;

			case "sell":
				return $this->sell();
				break;
		}
	}

	// Buy.
	private function buy() {
		$average = $this->ruler();
		Writer::write("debugg/scavenger.txt", $this->symbol, "Ruler price: " . $average);
	
		if (isset($this->timeblocks)) {
			if (count($this->timeblocks) >= 2) {
				$difference = $this->calculate($average, $this->lowPoint);

				Writer::write("debugg/scavenger.txt", $this->symbol, "Entry point difference percentage: " . $difference);

				if ($this->lowPoint <= $average) {
					if ($this->highPoint < $average) {
						$this->highPoint = $average;

						if ($this->entryPoint <= $difference) {
							$this->buyPoint = $this->highestPrice();
							$this->mode = "sell";

							Writer::write("debugg/sales.txt", $this->symbol, "Bought: " . $this->quantity() . " at " . $this->highestPrice() . " each");

							Writer::write("debugg/scavenger.txt", $this->symbol, "Switch transaction mode to sell");

							// Signal we want to buy.
							return [
								"type" => "buy",
								"price" => $this->highestPrice(),
								"quantity" => $this->quantity()
							];
						} else {
							Writer::write("debugg/sales.txt", $this->symbol, $this->entryPoint . " - " . $difference);
						}				
					} 
				} else {	
					$this->lowPoint = $average;

					Writer::write("debugg/scavenger.txt", $this->symbol, "Low point exceeded. Session destroyed.");		
				}
			} else {
				$this->lowPoint = $average;
				$this->highPoint = $average;

				Writer::write("debugg/scavenger.txt", $this->symbol, "Creating compareable timeblock");
			}
		}

		// Signal back that we don't want to buy.
		return false;
	}

	private function sell() {
		$average = $this->ruler();
		Writer::write("debugg/scavenger.txt", $this->symbol, "Ruler price: " . $average);	

		if ($this->buyPoint < $average) {
			if ($this->highPoint < $average) {
				$this->highPoint = $average;

				Writer::write("debugg/scavenger.txt", $this->symbol, "High point exceeded");
			} else {
				$difference = $this->calculate($this->highPoint, $average);
				Writer::write("debugg/scavenger.txt", $this->symbol, "Profit difference percentage: " . $difference);

				if ($difference >= $this->exitPoint) {
					$this->mode = "buy";
					$this->lowPoint = $average;
					$this->highPoint = $average;


					Writer::write("debugg/sales.txt", $this->symbol, "Sold: " . $this->highestPrice() . PHP_EOL);
					Writer::write("debugg/scavenger.txt", $this->symbol, "Switch transaction mode to buy");

					// Signal to the excecutive to buy.
					return [
						"type" => "sell",
						"price" => $this->highestPrice(),
						"quantity" => $this->quantity()
					];
				}
			}
		} else {
			// Possible loss sell.
			$difference = $this->calculate($this->highPoint, $average);
			Writer::write("debugg/scavenger.txt", $this->symbol, "Loss percentage: " . $difference);

			if ($this->exitPoint <= $difference) {
				$order = [
					"type" => "sell",
					"price" => $this->highestPrice(),
					"quantity" => $this->quantity()
				];

				$this->mode = "buy";

				Writer::write("debugg/sales.txt", $this->symbol, "Sold: " . $this->highestPrice() . PHP_EOL);
				Writer::write("debugg/scavenger.txt", $this->symbol, "Switch transaction mode to buy");

				// Signal to the excecutive to buy.
				return $order;
			}
		}
	}

	private function calculate($point1, $point2) {
		return ($point1 - $point2) / $point2 * 100;
	}

	private function highestPrice() {
		return end($this->timeblocks)->getHighest(); 
	}

	private function average() {
		return end($this->timeblocks)->getAverage();
	}

	private function ruler() {
		return end($this->timeblocks)->getRuler();
	}

	private function quantity() {
		return round($this->dollarToBTC() / $this->highestPrice());
	}

	private function dollarToBTC() {
		return $this->quantity / end($this->timeblocks)->btc;	
	}

	// Recieve and store injected timeblock.
	public function injectTimeblock($timeblock) {
		$this->timeblocks[] = $timeblock;

		if (count($this->timeblocks) > 5) {
			// Reset key indexes.
			$this->timeblocks = array_values($this->timeblocks);

			// Remove outdated timeblock.
			unset($this->timeblocks[0]);

			Writer::write("debugg/scavenger.txt", $this->symbol, "Unusable timeblock destroyed");
		}
	}

	// 
	public function getTimeblocks() {
		return $this->timeblocks;
	}

	// Load config file for scavenger object.
	private function loadConfig($symbol) {
		$file = file_get_contents("../../config/symbols/". $symbol . "/config.json") or die("File not found for " . $symbol . ".");
		$file = json_decode($file);

		// Set properties.
		$this->entryPoint = $file->config->entryPoint;
		$this->exitPoint = $file->config->exitPoint;
		$this->quantity = $file->config->quantity;
	}
}