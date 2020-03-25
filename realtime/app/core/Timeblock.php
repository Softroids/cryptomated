<?php

namespace Realtime;

class Timeblock {
	private $prices = [];
	public $btc;

	public function injectPrice($price) {
		$this->prices[] = $price;
		Writer::write("debugg/scavenger.txt", "Timeblock", "Price injected: " . $price);
	}

	public function getAverage() {
		return array_sum($this->prices) / count($this->prices);
	}

	public function getRuler() {
		$count = array_count_values($this->prices); 
		return array_search(max($count), $count);
	}

	public function getHighest() {
		return max($this->prices);
	}

	public function getLowest() {
		return min($this->prices);
	}

	public function getPrices() {
		return $this->prices;
	}
} 