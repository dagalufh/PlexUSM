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

$ReturnValue = file_get_contents($Server . "/library/sections/".$_GET['LibraryID']."/refresh?force=1");
USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Forced refresh started on the current section (SectionID: " . $_GET['LibraryID']."). This can't be monitord but will take a while to complete. Can be seen in Plex Activity Log");
?>
/**
 * After completion, reload the page.
 */
?>
<script>
	window.parent.location.href = window.parent.location.href;
</script>