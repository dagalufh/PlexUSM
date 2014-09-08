<?php
session_start();
/** Created by Mikael Aspehed **/
// Assumptions: All movies/shows are in a own subfolder.
// To be added:
// View subtitle, open a new window and read it. - Done
// Upload to Local folder - Not top of the list....
// Delete Subtitles - Done
// Prettify the code.
// Verify input from users.


/**
 * Changelog
 * V0.2
 * Divided code, working part and display part. For easier maintance and reading.
 * Added search function - can search for episodes or shows/movies. Only in the right Library. Not cross-library search.
 * Added options to control output a bit more. Hide some unwanted etc.
 * Added option to hide IDs.
 * In code: Added classes to manage it all. Rebuilt most of it.
 * Redesigned it.
 * Added checking for .srt, .smi, .ass, .ssa
 * Added check for subtitles locally stored that matches movie name.
 * 
 * V0.1
 * First release, lists all subtitles in a rather crude maner.
 *  
 */ 

include("ListDir.php");
include("classes.php");

// Settings
$Username = "l-admin";
$PathToPlexMediaFolder = "C:/users/".$Username."/AppData/Local/Plex Media Server/Media/Localhost/";
$PathToPlexDatabase = "c:/users/".$Username."/AppData/Local/Plex Media Server/Plug-in Support/Databases/com.plexapp.plugins.library.db";
$db = new SQLite3($PathToPlexDatabase);
/**
 * Define some variables used.
 */
 $ArrayVideos = array();
 $showID = false;
 $Searchstring = "";
 $showID_additionalquery = "parent_id is null";
 $MenuItem = "";
 $CurrentLibraryID = false;
 $CurrentLibraryName = "";
 $ErrorOccured = false;
 
/** 
 * Define Options and check if a user has changed them.
 */
if(!isset($_SESSION['Option_HideLocal']['set'])) {
	$_SESSION['Option_HideLocal']['set'] = false;
	$_SESSION['Option_HideLocal']['checked'] = "";
	$_SESSION['Option_MultipleSubtitlesOnly']['set'] = false;
	$_SESSION['Option_MultipleSubtitlesOnly']['checked'] = "";
	$_SESSION['Option_HideEmpty']['set'] = false;
	$_SESSION['Option_HideEmpty']['checked'] = "";		
	$_SESSION['Option_HideID']['set'] = false;
	$_SESSION['Option_HideID']['checked'] = "";	
}

if(isset($_POST['SaveOptions'])) {
	if(isset($_POST['OnlyMultiple'])) {
		$_SESSION['Option_MultipleSubtitlesOnly']['set'] = true;
		$_SESSION['Option_MultipleSubtitlesOnly']['checked'] = "checked";
	} else {
		$_SESSION['Option_MultipleSubtitlesOnly']['set'] = false;
		$_SESSION['Option_MultipleSubtitlesOnly']['checked'] = "";		
	}
	
	if(isset($_POST['HideLocal'])) {
		$_SESSION['Option_HideLocal']['set'] = true;
		$_SESSION['Option_HideLocal']['checked'] = "checked";
	} else {
		$_SESSION['Option_HideLocal']['set'] = false;
		$_SESSION['Option_HideLocal']['checked'] = "";		
	}
	
	if(isset($_POST['HideEmpty'])) {
		$_SESSION['Option_HideEmpty']['set'] = true;
		$_SESSION['Option_HideEmpty']['checked'] = "checked";
	} else {
		$_SESSION['Option_HideEmpty']['set'] = false;
		$_SESSION['Option_HideEmpty']['checked'] = "";		
	}
	
	if(isset($_POST['HideID'])) {
		$_SESSION['Option_HideID']['set'] = true;
		$_SESSION['Option_HideID']['checked'] = "checked";
	} else {
		$_SESSION['Option_HideID']['set'] = false;
		$_SESSION['Option_HideID']['checked'] = "";		
	}	
}

/**
 * Verify user inputs 
 */
 if (isset($_GET['libraryID'])) {
	 if (is_numeric($_GET['libraryID']) === false) {
	 	$ErrorOccured = true;
	 } else {
	 	$CurrentLibraryID = $db->escapeString($_GET['libraryID']);
	 }
 }
 
 if (isset($_GET['startLimit'])) { 
	 if (is_numeric($_GET['startLimit']) === false) {
	 	$ErrorOccured = true;
	 } else {
	 	$startLimit = $db->escapeString($_GET['startLimit']);
	 }
 }
 
 if(isset($_POST['Search'])) {
	 	$Searchstring = $db->escapeString($_POST['searchCriteria']);
 }

 if ( (isset($_GET['showID'])) and (is_numeric($_GET['showID']) === false)) {
 	$ErrorOccured = true;
 } elseif(isset($_GET['showID'])) {
 	$showID = $db->escapeString($_GET['showID']);
 	$showID_additionalquery = "parent_id='".$showID."'";
 }

/**
 * Build the menu to be shown at the top
 */	 
if( (isset($_GET['libraryID'])) and ($ErrorOccured === false)) {

	
	$count_result = $db->query("SELECT count(id) from metadata_items where ". $showID_additionalquery ." and library_section_id='".$CurrentLibraryID."'");
	$count = $count_result->fetchArray();
	$PageNr = 1;
	if($count[0]>50) {
		for ($i=0;$i<$count[0];$i=$i+50) {
			if($i==0) {
				$MenuItem .= "Pages: ";
			}
			if($i == $startLimit) {
				$MenuItem .= "<a href='index.php?libraryID=".$CurrentLibraryID."&startLimit=".$i."'><b>".$PageNr . "</b></a> ";
			} else {
				$MenuItem .= "<a href='index.php?libraryID=".$CurrentLibraryID."&startLimit=".$i."'>".$PageNr . "</a> ";
			}
			$PageNr++;
		}
	}
}


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Plex Unofficial Subtitle Manager</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<script>
		function confirmSubmit(formname, option)
		{
			var agree=confirm("Are you sure you wish to delete the selected subtitle(s)?");
			if (agree) {
				document.getElementById(formname).action = 'delete.php';
			    document.getElementById(formname).submit();
			} else {
			    return false ;
			}
		}
	</script>
</head>
<body>
<div id="Box">
	<div id="MenuBar" class="headline"><a href='index.php'>Home</a></div><div id="PageBar" class="headline"><?php echo $MenuItem?></div>
	<div id="MenuBox">
	<?php
		/**
		 * Get the libraries that are in the database and present them to the user.
		 */ 
		$results = $db->query('SELECT id, name FROM library_sections order by name');
		echo "<table cellspacing=0 cellpadding=0 style='width: 100%'><tr class='headline'>";
		if($_SESSION['Option_HideID']['set'] === false) {
				echo "<td>ID</td>";
			}	
		echo "<td>Title</td></tr>";
		while ($row = $results->fetchArray()) {	
			$LibraryName = $row['name'];
			if($row['id'] == $CurrentLibraryID) {
				$CurrentLibraryName = $row['name'];
				$LibraryName = "<b>" . $row['name'] . "</b>";
			}
			echo "<tr class='hovering'>";
			if($_SESSION['Option_HideID']['set'] === false) {
				echo "<td class='mainText'>".$row['id']."</td>";
			}
			echo "<td class='mainText'><a href='?libraryID=".$row['id']."&startLimit=0'>".$LibraryName."</a></td></tr>";
		}
		echo "</table>";
		
		if($CurrentLibraryID !== false) {
			echo "<br>";
			echo "<form name='searchForm' method='post' action=''>";
			echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
			echo "<tr class='headline'><td>Search: ".$CurrentLibraryName."</td></tr>";
			echo "<tr><td class='mainText'>Enter title to search for:</td></tr>";
			echo "<tr><td class='mainText'><input type='text' name='searchCriteria' value='".$Searchstring."'></td></tr>";
			echo "<tr><td class='mainText'><input type='submit' name='Search' value='Search'></td></tr>";
			echo "</table>";
			echo "</form>";
		}
		echo "<br>";
		echo "<form name='optionsForm' method='post' action=''>";
		echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
		echo "<tr class='headline'><td>Options</td></tr>";
		echo "<tr><td class='mainText'><input type='checkbox' name='HideLocal' ".$_SESSION['Option_HideLocal']['checked'].">Hide local subtitles</td></tr>";
		echo "<tr><td class='mainText'><input type='checkbox' name='HideEmpty' ".$_SESSION['Option_HideEmpty']['checked'].">Hide videos without subtitles</td></tr>";	
		echo "<tr><td class='mainText'><input type='checkbox' name='HideID' ".$_SESSION['Option_HideID']['checked'].">Hide IDs</td></tr>";	
		echo "<tr><td class='mainText'><input type='checkbox' name='OnlyMultiple' ".$_SESSION['Option_MultipleSubtitlesOnly']['checked'].">Show only multiple subtitles/language</td></tr>";
		echo "<tr><td class='mainText'><input type='submit' name='SaveOptions' value='Save'></td></tr>";
		echo "</table>";
		echo "</form>";
		echo "<br>";
		echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
		echo "<tr class='headline'><td>Recommended usage</td></tr>";
		echo "<tr><td class='mainText'>";
		echo "1. Disable the subtitles agents<br>";
		echo "2. Remove the once you want via this script.<br>";
		echo "3. Go to the movies you altered and update them, searching for new metadata.<br>";
		echo "4. Re-enable subtitle agents if you want. You should now have a cleaner subtitle list.<br><br>";
		echo "</td></tr>";
		echo "<tr class='headline'><td>Information & Features</td></tr>";		
		echo "<tr><td class='mainText'>";
		echo "This is an unofficial manager for subtitles.<br>";
		echo "<b>Usage is on your own risk!</b><br><br>";
		echo "Current features:<br>";
		echo "<ul>";
		echo "<li>List all subtitles for a movie/tv show in your library. Both local (next to the movie in it's folder or one subfolder) and in the c:\users appdata folder.</li>";
		echo "<li>View the subtitle and see it's contents to determine what to delete.</li>";
		echo "<li>Delete selected subtitle from the harddrive.</li>";
		echo "<li>Search for videos.</li>";
		echo "<li>Options for output.</li>";
		echo "</ul>";
		echo "Planed features:<br>";
		echo "<ul>";
		echo "<li>Upload subtitles to the folder where the video file is stored.</li>";
		echo "</ul>";
		echo "</td></tr>";
		echo "</table>";
	?>
	</div>	
	<div id="MainBox">
	<?php
	if( (isset($_GET['libraryID'])) and ($ErrorOccured === false)) {
		
		/**
		 * Fetch data from the metadata_items table based on selected library and limit.
		 */ 
		/**
		 * Check if the user has done a search and create a resource from the database based on it.
		 */ 
		 if(isset($_POST['Search']) and (strlen($Searchstring)>1) ) {
		 	// Always fetch everything that has no parrent. And then, fetch them as we find them along the way.
		 	$results = $db->query('SELECT id, title, parent_id, [index] FROM metadata_items where title LIKE "%'.$Searchstring.'%"  and library_section_id='.$CurrentLibraryID.' order by title, [index] limit '.$startLimit.',50');	
		 } else {
		 	$results = $db->query('SELECT id, title, parent_id, [index] FROM metadata_items where library_section_id='.$CurrentLibraryID.' and '. $showID_additionalquery .' order by title, [index] limit '.$startLimit.',50');	
		 }	 	
		
		/* Recursive function? */
		while ($row = $results->fetchArray()) {			
	
		 
			/**
			 * Create a Video object for each video;
			 */
			if(strlen($row['title'])<1) {
				$row['title'] =  "Season " . $row['index'];	
			} 
			$CurrentVideo = new Video($row['id'],$row['title']);
			$CurrentVideo->setLibraryID($CurrentLibraryID);
			$CurrentVideo->setParentID($row['parent_id']);
			
			$result_count_children = $db->query("SELECT count(id) from metadata_items where parent_id='".$CurrentVideo->getID()."'");
			$count = $result_count_children->fetchArray();
			$CurrentVideo->setNumberOfChilds($count[0]);
			
			$results_media_items = $db->query('SELECT id FROM media_items where metadata_item_id='.$CurrentVideo->getID());
			$media_items = $results_media_items->fetchArray();
			
			/** Fetch and prepare path fo subtitle. */
			$results_hash = $db->query('SELECT hash,file,media_item_id FROM media_parts where media_item_id="'.$media_items['id'].'"');
			$row_hash = $results_hash->fetchArray();
			
			$CurrentVideo->setHash($row_hash['hash']);
			$CurrentVideo->setPath($row_hash['file']);
			$Folder = SepFilename(preg_replace("/\\\\/i", "/", $row_hash['file']));
			$VideoNameWithoutEnding = substr($Folder[1],0,strrpos($Folder[1],"."));
				
			$UpperDir = substr($CurrentVideo->getHash(),0,1);
		   	$LowerDir = substr($CurrentVideo->getHash(),1).".bundle";
		
		
			/**
			 * Check if this is infact a tv-show, then don't list subtitles.
			 */ 
			 $results_library_type = $db->query('select section_type from library_sections where id='.$CurrentLibraryID);
			 $library_type = $results_library_type->fetchArray();
			 $CurrentVideo->setLibraryType($library_type['section_type']);
			 /**
			  * 1 = Movie, 2 = TV-Show
			  */ 
			 if((!($library_type['section_type'] == 2)) or ($CurrentVideo->getParentID() == $showID)){
				 /**
			     * Get all the subtitles located in the Plex Media Server AppData folder and present them.
			     */
			    if(file_exists($PathToPlexMediaFolder.$UpperDir."/".$LowerDir) === true) {
					$SubtitlesInDirectory = ListDir($PathToPlexMediaFolder.$UpperDir."/".$LowerDir . "/Contents",5);	
					foreach($SubtitlesInDirectory as $CurrentSubtitle) {
						
							if( (strpos($CurrentSubtitle,".srt") !== false) or (strpos($CurrentSubtitle,".ass") !== false) or (strpos($CurrentSubtitle,".ssa") !== false) or (strpos($CurrentSubtitle,".smi") !== false) ) {
								$Filename = SepFilename($CurrentSubtitle);
								$Filename[0] = explode("/",$Filename[0]);
								if(stripos($Filename[1],$VideoNameWithoutEnding) !== false) {
									$CurrentVideo->setNewSubtitle(new Subtitle($Filename[1], $Filename[0][count($Filename[0])-2], $CurrentSubtitle, "Agents"));
								}
							}
						
					}
				}    
				
				/**
				 * Use the files folder to locate any subtitles that are either in the same directory or one below the file.
				 * Local subtitles always has to have the same name as the file. We check that the subtitles found match that.
				 * Check for subtitles ending with .srt .ass .ssa .smi
				 */
				if($_SESSION['Option_HideLocal']['set'] === false) { 
				//	$Folder = SepFilename(preg_replace("/\\\\/i", "/", $row_hash['file']));
					if(file_exists($Folder[0]) === true) {
						$var_Local = ListDir($Folder[0],1);		
						foreach($var_Local as $CurrentSubtitle) {
							if( (strpos($CurrentSubtitle,".srt") !== false) or (strpos($CurrentSubtitle,".ass") !== false) or (strpos($CurrentSubtitle,".ssa") !== false) or (strpos($CurrentSubtitle,".smi") !== false) ) {
								$Filename = SepFilename($CurrentSubtitle);
								$Filename[0] = explode("/",$Filename[0]);
								if(stripos($Filename[1],$VideoNameWithoutEnding) !== false) {
									$CurrentVideo->setNewSubtitle(new Subtitle($Filename[1], $Filename[0][count($Filename[0])-1], $CurrentSubtitle, "Local"));
								}
							}
						}
					}  
				}
			}
			
			$ArrayVideos[] = $CurrentVideo;	
		}
		
		/**
		 * Outputs
		 */
		echo "<table cellspacing=0 cellpadding=0 class='TableLayout'><tr class='headline'><td class='small'>";
		if($_SESSION['Option_HideID']['set'] === false) {
			echo "ID";
		}
		echo "</td><td class='mediumlong'>Title</td><td class='extralong' colspan='2'>File</td></tr>"; 
		foreach ($ArrayVideos as $Video) {
			if( ($Video->getLibraryType() == 2) and ($Video->getNumberOfChilds()>0) ){
				echo "<tr><td class='mainText'>";
				if($_SESSION['Option_HideID']['set'] === false) {
					echo $Video->getID();
				}
				echo "</td><td class='mainText'><a href='index.php?libraryID=".$Video->getLibraryID()."&startLimit=0&showID=".$Video->getID()."'>".$Video->getTitle()."</a></td></tr>";	
			} else {
				if(($_SESSION['Option_HideEmpty']['set'] === true) and (count($Video->getSubtitles())<1) ) {
				} else {	
					echo "<form id='".$Video->getID()."' name='".$Video->getID()."' method='POST' target='WorkFrame'>";
					 	echo "<tr class='entryheadline'><td class='mainText'>";
					 		if($_SESSION['Option_HideID']['set'] === false) {
					 			echo $Video->getID();
					 		}
					 		echo "</td><td class='mainText'>".$Video->getTitle()."</td>" . "<td class='mainText'>".$Video->getPath()."</td><td class='mainText long'><span class='link' onclick='confirmSubmit(\"".$Video->getID()."\");'>Delete Selected</span></td>";
					 	echo "</tr>";
					 	
					 	/**
					 	 * Print out subtitles if it's not a librarytype 2.
					 	 */ 
					 	 
					 		foreach ($Video->getSubtitles() as $SubtitleLanguageArray) {
					 			if(($_SESSION['Option_MultipleSubtitlesOnly']['set'] === true) and (count($SubtitleLanguageArray)<2)) {
					 			} else {	
					 			foreach ($SubtitleLanguageArray as $Subtitle) {
					 				$Language = "";
					 				if(strlen($Subtitle->getLanguage())>0) {
					 					$Language =  $Subtitle->getLanguage() . "/";
					 				}
						 			echo   "<tr class='hovering'><td class='mainText'><input type='checkbox' name='Subtitle[]' value=\"".$Subtitle->getPath()."\"></td><td class='mainText'>".$Subtitle->getSource()."</td><td class='mainText' colspan='2'><a target=\"_NEW\" href=\"ReadFile.php?FileToOpen=".$Subtitle->getPath()."\">View</a> ".$Language . $Subtitle->getFilename() . "</td></tr>";
					 			}
					 			}
					 		}
				 	echo "</form>";
					echo "<tr><td class='hidden'>&nbsp;</td></tr>";
				}
			}
		}
		echo "</table>";	
	} else{
		echo "Please select a library.";
	}
	?>
	</div>
</div>
<iframe name="WorkFrame" class="hiddenframe"></iframe>
</body>
</html>