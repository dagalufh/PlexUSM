<?php
// Deletes the provided files.

foreach ($_POST['Subtitle'] as $Subtitle) {
	if(file_exists($Subtitle)) {
		unlink($Subtitle);	
	}
}
// After work is done, reload the original window.
?>
<script>

	window.parent.location = document.referrer;
</script>