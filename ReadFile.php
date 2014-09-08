<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Plex Unofficial Subtitle Manager</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<?php
if(is_dir($_GET['FileToOpen'])) {
	?>
  		<div id="headline">An Error Has Occured</div>
		<div id="mainText">
			<p>The file sent to be open is in fact a directory!</p>
		</div>
	<?php
die();
}

$fp = fopen ($_GET['FileToOpen'], "r");
$bytes = filesize($_GET['FileToOpen']);
$buffer = fread($fp, $bytes);
fclose ($fp);
?>
	  		<div id="headline" ><?php echo $_GET['FileToOpen']?> (<?php echo round($bytes/1000,2)?>KB)</div>
			<div id="mainText" name="">
				<textarea id="FileContent" name="FileContent" class="EditText" wrap="off" readonly><?php echo htmlspecialchars($buffer)?></textarea>
			</div>
</body>
</html>