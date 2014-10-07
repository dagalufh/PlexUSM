<?php
session_start();
/**
 * The purpose of this file is to delete the selected files.
 *
 *
 * Include the file with the logfunction
 */
include("settings.php");
include("functions.php");

/**
 * Go through the selected checkboxes and remove the files.
 */
if( (count($_POST['Subtitle']) == "0") or (!isset($_POST['Subtitle'])) ) {
	USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Subtitle POST empty.\n");
} else {
	foreach ($_POST['Subtitle'] as $Subtitle) {
	
		list($MediaID,$SubtitleID,$Filename) = explode(":",$Subtitle);
		
		USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Request to delete SubtitleID(".$SubtitleID.") belonging to MovieID(".$MediaID.") with Filename(".$Filename.").");
		
		$ReturnValue = file_get_contents($Server . "/utils/devtools?Func=DelSRT&Secret=" . $DevToolsSecret . "&MediaID=".$MediaID . "&SrtFileID=".$SubtitleID);
		if($ReturnValue == "ok") {
			USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Successfully deleted subtitle: " . $Filename);
		} else {
			USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Failed deleting subtitle: " . $Filename . " Returnvalue: " . $ReturnValue);
		}
	}
}

/**
 * After completion, reload the page.
 */
?>
<script>
	window.parent.location.href = window.parent.location.href;
</script>