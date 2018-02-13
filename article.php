<!DOCTYPE html>
<html>
<head>
	<title>Search College</title>
	<link rel="stylesheet" href="style.css">
	<link rel="shortcut icon" href="favicon.png" >
</head>
<body>
	<div id="article">
	<?php
		$directory = "text/";
		$filecount = 0;
		$files = glob($directory."*");
		if ($files)
			$filecount = count($files)-1;
		$page = $_GET['pages'];
		$file = fopen($directory.$page."_teks.txt", 'r');
			$pageText = preg_split('/$\R?^/m', fread($file, 25000));
			foreach ($pageText as $key => $value) {
				if ($key == 0)
					echo "<div class='title'>".$value."</div>";
				else
					echo $value."<br>";
			}
	?>
	</div>
</body>
</html>