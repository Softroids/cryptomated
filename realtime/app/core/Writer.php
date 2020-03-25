<?php

namespace Realtime;

class Writer {
	public static function write($file, $symbol, $message) {
		file_put_contents("../../data/" . $file, date("H:i:s") . ": " . $symbol . ": " . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
	}
}