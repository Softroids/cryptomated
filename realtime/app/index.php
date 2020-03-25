<?php
	require_once("../../config/ini.php");

	if (isset($_POST["execute"])) {
		new Realtime\Executive();
	}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Realtime</title>
</head>
<body>
	<form method="post">
		<input type="submit" value="Execute" name="execute">
	</form>
</body>
</html>