<?php
include("settings.php");
include("functions.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Plex Unofficial Subtitle Manager</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	</head>
	<body>
		<?php
		$CorrectedFilename = preg_replace("/ /", "%20", $_GET['FileToOpen']);
		if(strpos($CorrectedFilename,"file://")!==false) {
			$CorrectedFilename = substr($CorrectedFilename,7);
		}
		if(exists($CorrectedFilename) !== false) {
			$ReturnValue = file_get_contents($Server . "/utils/devtools?Func=ShowSRT&Secret=" . $DevToolsSecret . "&FileName=".$CorrectedFilename);
		}
		?>
		<div id="headline" ><?php echo $_GET['FileToOpen']?></div>
		<div id="mainText" name="">
			<textarea id="FileContent" name="FileContent" class="EditText" wrap="off" readonly><?php echo htmlspecialchars($ReturnValue)?></textarea>
		</div>
	</body>
</html>