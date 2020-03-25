<?php

require_once("vendor/autoload.php");

set_time_limit(0);

if (isset($_POST["neverAgain"])) {

	define('API', [
		"Key" => "C4d59yCI0FOs9Hdgo0Aak57Gr1wy8czn8lJRRHWMWQCVl4k0OJrJvEqaPa7WBuDY",
		"Secret" => "N2GXPucSBsHFH7iuI45TitEqSHB8H9vJafMtlkbaJb7qAxwL9GEwG6I3GQicIXMQ"
	]);

	$binance = new \Binance\API(API["Key"], API["Secret"], ['useServerTime' => true]);
	$binance->useServerTime();
	$coins = $binance->prices();

	$counter = 1;
	$fileName = "3hr_" . $counter;
	$date = date("d-m-y");
	$time = time();

	while (1) {
		usleep(500000);
		$coins = $binance->prices();

		foreach ($coins as $coinPair => $price) {
			if ((time() - $time) >= 10800) {
		        $time = time();
		        $counter++;
		        $fileName = "3hr_" . $counter;
		    }

		    $dir = "data/" . $coinPair;
		    if (!is_dir($dir)) {
		    	mkdir($dir);
		    }

		    if ($counter == 9) {
		    	$counter = 1;
		    	$date = date("d-m-y");
		    	$dir = "data/" . $coinPair . "/" . date("d-m-y");
				if (!is_dir($dir)) {
					mkdir($dir);
				}
		    } else {
		    	$dir = "data/" . $coinPair . "/" . $date;
				if (!is_dir($dir)) {
					mkdir($dir);
				}
		    }

			if (is_dir($dir)) {
				$message = $price . PHP_EOL;
				file_put_contents("data/" . $coinPair . "/" . $date . "/" . $fileName . ".txt", $message, FILE_APPEND | LOCK_EX);
			}
		}
	}
}

?>

<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	<form method="post">
		<input type="submit" name="neverAgain" style="display: none">
	</form>
</body>
</html>