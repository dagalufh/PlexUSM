<?php
/**
 * The purpose of this file is to delete the selected files.
 *
 *
 * Include the file with the logfunction
 */
include("functions.php");

/**
 * Go through the selected checkboxes and remove the files.
 */
foreach ($_POST['Subtitle'] as $Subtitle) {
	if(file_exists($Subtitle)) {
		if (unlink($Subtitle)) {
			USMLog("info","Successfully deleted file:" . $Subtitle . "\n");
		} else {
			USMLog("error","Failed deleting file:" . $Subtitle . "\n");
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