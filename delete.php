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
		if(file_exists($Subtitle)) {
			if (unlink($Subtitle)) {
				USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Successfully deleted file: " . $Subtitle . "\n");
			} else {
				USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Failed deleting file: " . $Subtitle . "\n");
			}
		} else {
			USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Unable to find the file for deletion: " . $Subtitle . "\n");
		}
	}
}

/**
 * After completion, reload the page.
 */
?>
<script>
	window.parent.location.href = windows.parent.location.href;
</script>