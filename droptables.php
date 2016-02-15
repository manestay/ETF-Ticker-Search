<body>
	ETFs table dropped
	<?php
		$server = 'localhost';
		$user = 'orbis201_index';
		$pass = 'php';
		$db = 'orbis201_etfsdb';
		$conn = mysqli_connect($server, $user, $pass, $db);
		
		$sql = "DROPTABLE ETFs";
		mysqli_query($conn, $sql);
	?>
</body>