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
		USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Received request to delete file: " . $Subtitle);
		
		$CorrectedFilename = preg_replace("/ /", "%20", $Subtitle);
		
		if(exists($CorrectedFilename) !== false) {
			$ReturnValue = file_get_contents($Server . "/utils/devtools?Func=DelFile&Secret=" . $DevToolsSecret . "&File=".$CorrectedFilename);
			if($ReturnValue == "ok") {
				USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Successfully deleted file: " . $CorrectedFilename);
			} else {
				USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Failed deleting file: " . $CorrectedFilename . " Returnvalue: " . $ReturnValue);
			}
		} else {
			USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Unable to find the file for deletion: " . $CorrectedFilename . "\n");
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