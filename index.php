<?php
session_start();
/**
 * Thought:
 * Create a class - Shows, Subclass - Season - Episodes?
 *
 *
 * Changelog
 * V0.4
 * Completly moved away from database. Now uses the webapi.
 * Recommends to use devtool.bundle created by dane22 as that allows the script to self figure out the path to your plex media/localhost folder where agents store subtitles.
 * Added options to hide integrated subtitles.
 * Added tags that shows what subtitles are selected in the plex interface, so you know which subtitle to remove.
 * 
 * V.03
 * Added Settings.php
 *
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
include("settings.php");
include("functions.php");
$FolderCheck = CheckSettings();

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
$AdditionalLink_ShowKey = "";
$AdditionalLink_ParentKey = "";
$AdditionalLink_Searchstring = "";

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
	$_SESSION['Option_HideID']['set'] = true;
	$_SESSION['Option_HideID']['checked'] = "";	
	$_SESSION['Option_ItemsPerPage']['value'] = $ItemsPerPage;	
	$_SESSION['Option_HideIntegrated']['set'] = false;
	$_SESSION['Option_HideIntegrated']['checked'] = "";		

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

	if(isset($_POST['HideIntegrated'])) {
		$_SESSION['Option_HideIntegrated']['set'] = true;
		$_SESSION['Option_HideIntegrated']['checked'] = "checked";
	} else {
		$_SESSION['Option_HideIntegrated']['set'] = false;
		$_SESSION['Option_HideIntegrated']['checked'] = "";		
	}

	if(isset($_POST['ItemsPerPage']) and ($_POST['ItemsPerPage']>0)) {

		$_SESSION['Option_ItemsPerPage']['value'] = $_POST['ItemsPerPage'];
	} else {
		$_SESSION['Option_ItemsPerPage']['value'] = $ItemsPerPage;
	}	
}

/**
 * Verify user inputs 
 */
if (isset($_GET['libraryID'])) {
	if (is_numeric($_GET['libraryID']) === false) {
		$ErrorOccured = true;
	} else {
		$CurrentLibraryID = $_GET['libraryID'];
	}
}

if (isset($_GET['startLimit'])) { 
	if (is_numeric($_GET['startLimit']) === false) {
		$ErrorOccured = true;
	} else {
		$startLimit = $_GET['startLimit'];
	}
}

if(isset($_GET['searchCriteria'])) {
	$Searchstring = $_GET['searchCriteria'];
	$AdditionalLink_Searchstring = "&searchCriteria=" . $Searchstring;
}

if(isset($_GET['ShowKey'])) {
	$ShowKey= $_GET['ShowKey'];
	//$showID_additionalquery = "parent_id='".$showID."'";
	$AdditionalLink_ShowKey = "&ShowKey=".$_GET['ShowKey'];
}

if( (isset($_GET['ParentKey'])) and (strlen($_GET['ParentKey'])>18) ) {
	$ParentKey= $_GET['ParentKey'];
	$AdditionalLink_ParentKey = "&ParentKey=".$_GET['ParentKey'];

}


/**
 *
 */
if( (isset($_GET['libraryID'])) and ($ErrorOccured === false)){

	if( (isset($ShowKey)) and (!isset($ParentKey)) )  {
		get_show_seasons($ShowKey);	
	} elseif( (isset($ShowKey)) and (isset($ParentKey)) ) {
		get_show_episodes($ShowKey);
	} else {

		/** For the current section, list all the movies */
		$xmlsub = simplexml_load_file($Server . '/library/sections/'.$CurrentLibraryID.'/all');
		//$xmlsub = FetchXML('/library/sections/'.$CurrentLibraryID.'/all');
		foreach($xmlsub as $xmlrowsub) {	
			$AddVideo = true;

			$xmlsub2 = simplexml_load_file($Server.$xmlrowsub['key'].'/tree');
			//$xmlsub2 = FetchXML($xmlrowsub['key'].'/tree');
			if ($xmlrowsub['type'] == "show") {
				/**
				 * If it is a show, we don't need to search for subtitles.
				 */
				if(strlen($Searchstring)>3){
					/**
				 * If it is a show, we don't need to search for subtitles.
				 */

					$CurrentVideo = new Video($xmlrowsub['key'],$xmlrowsub['title']);
					$CurrentVideo->setLibraryID($CurrentLibraryID);
					$CurrentVideo->setType($xmlrowsub['type']);
					$CurrentVideo->setRatingKey($xmlrowsub['ratingKey']);
					$CurrentVideo->setSeasonIndex('0');
					$CurrentVideo->setEpisodeIndex('0');
					$CurrentVideo->setActiveSubtitle(0);
					$MatchingEpisodes = false;

					$SeasonXML = simplexml_load_file($Server . $CurrentVideo->getID() . '/all');
					//$SeasonXML = FetchXML($CurrentVideo->getID() . '/all');
					foreach($SeasonXML as $Season) {
						if(isset($Season->attributes()->index) !== false) {
							$MatchingEpisodes_Temp = get_show_episodes($Season->attributes()->key,$Season->attributes(),$CurrentVideo, $Searchstring);
							if( ($MatchingEpisodes === false) and ($MatchingEpisodes_Temp) ) {
								$MatchingEpisodes = true;
							}
						}

					}

					if( ($MatchingEpisodes) or (stripos($CurrentVideo->getTitle(),$Searchstring) !== false) )  {
						$ArrayVideos[] = $CurrentVideo;	
					}					

				} else {
					$CurrentVideo = new Video($xmlrowsub['key'],$xmlrowsub['title']);
					$CurrentVideo->setLibraryID($_GET['libraryID']);
					$CurrentVideo->setType($xmlrowsub['type']);
					$CurrentVideo->setActiveSubtitle(0);
					$ArrayVideos[] = $CurrentVideo;	
				}
			} else {
				/**
				 * Movies can have subtitles, search for them.
				 */
				foreach($xmlsub2 as $xmlrowsub2) {

					$CurrentMediaPart= $xmlrowsub2->MediaItem->MediaPart;
					$CurrentVideo = new Video($xmlrowsub2->attributes()->id,$xmlrowsub2->attributes()->title);
					//print_r($xmlrowsub2);
					//echo "<br><br>";
					/**
					 * Check if we have a searchstring, and if so, check if the current title matches it.
					 */
					if(strlen($Searchstring)>3){
						if(stripos($CurrentVideo->getTitle(),$Searchstring) === false) {
							continue;
						}
					}

					$ActiveSubtitleXML = simplexml_load_file($Server.$xmlrowsub['key']);
					//$ActiveSubtitleXML = FetchXML($xmlrowsub['key']);
					foreach($ActiveSubtitleXML as $ActiveSubtitle) { 
						$Streams = $ActiveSubtitle->Media->Part->Stream;
						foreach($Streams as $ActiveSubtitle) {
							if( ($ActiveSubtitle->attributes()->streamType == 3) and (isset($ActiveSubtitle->attributes()->selected)) ) {
								$CurrentVideo->setActiveSubtitle($ActiveSubtitle->attributes()->id);
							}
						}	
					}


					$CurrentVideo->setLibraryID($_GET['libraryID']);
					$CurrentVideo->setType($xmlrowsub['type']);
					$CurrentVideo->setEpisodeIndex(0);

					$CurrentVideo->setHash($CurrentMediaPart->attributes()->hash);
					$CurrentVideo->setPath($CurrentMediaPart->attributes()->file);

					foreach($CurrentMediaPart->MediaStream as $subtitle) {
						if($subtitle->attributes()->type == 3) {

							// Filter out VOBSUB by checking that there is a url connected to the subtitle.
							$Language = "-";
							if(strlen($subtitle->attributes()->language)>0) {
								$Language = $subtitle->attributes()->language;
							}
							
							if(isset($subtitle->attributes()->url)) {
								$LocalSubtitle = false;
								$Folder = SepFilename(preg_replace("/\\\\/i", "/", $subtitle->attributes()->url));

								// Subtitles - All of them
								
								if(strpos($Folder[0],"media://")!==false) {
									$Folder[0] = $PathToPlexMediaFolder . substr($Folder[0],8);
								} else {
									$LocalSubtitle = true;
								}
								if($_SESSION['Option_HideLocal']['set'] === false) { 

									$CurrentVideo->setNewSubtitle(new Subtitle($subtitle->attributes()->id, $Folder[1], $Language, $Folder[0] . "/" . $Folder[1], $subtitle->attributes()->codec));
								} else {
									if($LocalSubtitle === false) {
										$CurrentVideo->setNewSubtitle(new Subtitle($subtitle->attributes()->id, $Folder[1], $Language, $Folder[0] . "/" . $Folder[1], $subtitle->attributes()->codec));
									}
								}

							} else {
								if($_SESSION['Option_HideIntegrated']['set']  === false) {
									$CurrentVideo->setNewSubtitle(new Subtitle($subtitle->attributes()->id, "Integrated subtitle", $Language,  false, $subtitle->attributes()->codec));	
								}
							}
						}
					}


					foreach ($CurrentVideo->getSubtitles() as $SubtitleLanguageArray) {
						if(($_SESSION['Option_MultipleSubtitlesOnly']['set'] === true) and (count($SubtitleLanguageArray)<2)) {
							$AddVideo = false;	
						}
					}

					if(($_SESSION['Option_HideEmpty']['set'] === true) and (count($CurrentVideo->getSubtitles())<1) ) {
						$AddVideo = false;
					}

					if($AddVideo) {
						$ArrayVideos[] = $CurrentVideo;	
					}

				}
			}
		}

	}	
	usort($ArrayVideos,"SortVideos");
}

/**
 * Build the menu to be shown at the top
 */	 
if( (isset($_GET['libraryID'])) and ($ErrorOccured === false)) {
	$count = count($ArrayVideos);
	$PageNr = 1;
	if($count>$_SESSION['Option_ItemsPerPage']['value']) {
		for ($i=0;$i<$count;$i=$i+$_SESSION['Option_ItemsPerPage']['value']) {
			if($i==0) {
				$MenuItem .= "Pages: ";
			}
			if($i == $startLimit) {
				$MenuItem .= "<a href='index.php?libraryID=".$CurrentLibraryID."&startLimit=".$i.$AdditionalLink_ShowKey.$AdditionalLink_ParentKey.$AdditionalLink_Searchstring."'>[".$PageNr . "]</a> ";
			} else {
				$MenuItem .= "<a href='index.php?libraryID=".$CurrentLibraryID."&startLimit=".$i.$AdditionalLink_ShowKey.$AdditionalLink_ParentKey.$AdditionalLink_Searchstring."'>".$PageNr . "</a> ";
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
		<table cellpadding="0" cellspacing="0" class="BoxTable">
			<tr><td class="BoxTable_td mediumlong">

				<div id="MenuBar" class="headline"><a href='index.php'>Home</a></div>
				<div id="MenuBox">

					<?php
/**
* Get the libraries that are in the database and present them to the user.
*/ 
echo "<table cellspacing=0 cellpadding=0 style='width: 100%'><tr class='headline'>";
if($_SESSION['Option_HideID']['set'] === false) {
	echo "<td>ID</td>";
}	
echo "<td>Title</td></tr>";
$xml = simplexml_load_file($Server . '/library/sections');
//$xml = FetchXML('/library/sections');
foreach($xml as $xmlrow) {
	$Section = $xmlrow->attributes();			
	$LibraryName = $Section->title;
	if($Section->key == $CurrentLibraryID) {
		$CurrentLibraryName = $Section->title;
		$LibraryName = "<b>" . $Section->title . "</b>";
	}
	echo "<tr class='hovering'>";
	if($_SESSION['Option_HideID']['set'] === false) {
		echo "<td class='mainText'>".$Section->key."</td>";
	}
	echo "<td class='mainText'><a href='?libraryID=".$Section->key."&startLimit=0'>".$LibraryName."</a></td></tr>";

}
echo "</table>";
echo "<br>";

if($CurrentLibraryID !== false) {
	echo "<br>";
	echo "<form name='searchForm' method='GET' action=''>";
	echo "<input type='hidden' name='libraryID' value='".$CurrentLibraryID."'>";
	echo "<input type='hidden' name='startLimit' value='0'>";
	echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
	echo "<tr class='headline'><td>Search: ".$CurrentLibraryName."</td></tr>";
	echo "<tr><td class='mainText'>Enter title to search for:</td></tr>";
	echo "<tr><td class='mainText'><input type='text' name='searchCriteria' value='".$Searchstring."'></td></tr>";
	echo "<tr><td class='mainText'><input type='submit'></td></tr>";
	echo "</table>";
	echo "</form>";
}
echo "<br>";
echo "<form name='optionsForm' method='post' action=''>";
echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
echo "<tr class='headline'><td>Options</td></tr>";
echo "<tr><td class='mainText'><input type='checkbox' name='HideLocal' ".$_SESSION['Option_HideLocal']['checked'].">Hide local subtitles</td></tr>";
echo "<tr><td class='mainText'><input type='checkbox' name='HideEmpty' ".$_SESSION['Option_HideEmpty']['checked'].">Hide videos without subtitles</td></tr>";	
echo "<tr><td class='mainText'><input type='checkbox' name='HideIntegrated' ".$_SESSION['Option_HideIntegrated']['checked'].">Hide integrated subtitles</td></tr>";
echo "<tr><td class='mainText'><input type='checkbox' name='OnlyMultiple' ".$_SESSION['Option_MultipleSubtitlesOnly']['checked'].">Show only multiple subtitles/language</td></tr>";
echo "<tr><td class='mainText'><input type='text' size='2' name='ItemsPerPage' value='".$_SESSION['Option_ItemsPerPage']['value']."'>Items per page</td></tr>";
echo "<tr><td class='mainText'><input type='submit' name='SaveOptions' value='Save'></td></tr>";
echo "</table>";
echo "</form>";
echo "<br>";
echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
echo "<tr class='headline'><td>Recommended usage</td></tr>";
echo "<tr><td class='mainText'>";
echo "1. Disable the subtitles agents<br>";
echo "2. Remove the subtitles you want via this script.<br>";
echo "3. Go to the movies you altered and update them, searching for new metadata.<br>";
echo "4. Re-enable subtitle agents if you want. You should now have a cleaner list of subtitles.<br><br>";
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
echo "</td></tr>";
echo "</table>";
					?>
				</div>
				</td><td class="BoxTable_td">
				<div id="PageBar" class="headline"><?php echo $MenuItem?></div>
				<div id="MainBox">
					<?php	
						if( (isset($_GET['libraryID'])) and ($ErrorOccured === false)){	
						/*
						* For each item in the ArrayVideos array.. check if we reached the max limit for items per page.
						*/
						for ($i=$startLimit;$i<count($ArrayVideos);$i++) {
							if($i == ($startLimit+$_SESSION['Option_ItemsPerPage']['value'])) {
								break;
							}
							$Video = $ArrayVideos[$i];
							$AdditionalShowOutput = "";
							echo "<div class='VideoBox'>";		

							if($Video->getType() == "show"){
								echo "<div class='VideoHeadline'><a href='index.php?libraryID=".$Video->getLibraryID()."&startLimit=0&ShowKey=".$Video->getID()."&ParentKey=".$Video->getParentID()."'>".$Video->getTitle()."</a></div>";
							} else {

								echo "<form id='".(string)$Video->getID()."' name='".(string)$Video->getID()."' method='POST' target='WorkFrame'>";

								if($_SESSION['Option_HideID']['set'] === false) {
									//echo $Video->getRatingKey() . "." . $Video->getSeasonIndex() . "." . $Video->getEpisodeIndex();
								}
								if(strlen($Video->getTitleShow())>0) {
									$AdditionalShowOutput = (string)$Video->getTitleShow() ."/". (string)$Video->getTitleSeason() . "/";	
								}
								echo "<div class='VideoHeadline'>" . $AdditionalShowOutput . $Video->getTitle() . "</div>";
								echo "<div class='VideoPath'>".$Video->getPath()."</div>";

								/**
					 	 * Print out subtitles if it's not a librarytype 2.
					 	 */ 

								foreach ($Video->getSubtitles() as $SubtitleLanguageArray) {	
									foreach ($SubtitleLanguageArray as $Subtitle) {
										$Language = "";
										$Active = "";
										$View = "";
										$Checkbox = "";
										$Exists = "";
										$AddClass = false;

										if(strlen($Subtitle->getLanguage())>0) {
											$Language =  $Subtitle->getLanguage();
										}


										if( ($Video->getActiveSubtitle()>0) and ((int)$Video->getActiveSubtitle() == (int)$Subtitle->getID()) ){

											$Active = "Selected subtitle in Plex";
											$AddClass = "Active";
										}

										if($Subtitle->getPath() !== false) {
											$View = "<a target=\"_NEW\" href=\"ReadFile.php?FileToOpen=".$Subtitle->getPath()."\">View</a> ";
											$Checkbox = "<input type='checkbox' name='Subtitle[]' value=\"".$Subtitle->getPath()."\">";
										}

										if( (file_exists($Subtitle->getPath()) === false) and ($Subtitle->getPath() !== false) ) {
											$Exists = "Not found on disk. Please update Plex library.";
											$Checkbox = "";
											$View = "";
											$AddClass = "Removed";
										}

										echo "<div class='VideoSubtitle hovering " . $AddClass . "'>";
										echo "<table class='Max' cellspacing=0 cellpadding=0><tr><td class='small'>" . $Checkbox . "</td><td class='small'>" . $Subtitle->getSource() . "</td><td class='small'>" .$Language ."</td><td>". $Subtitle->getFilename() . "</td><td class='small'>" . $View . "</td></tr>";
										if ($AddClass !== false) {
											echo "<tr><td></td><td></td><td></td><td>Message: " . $Active . $Exists . "</td></tr>";
										}
										echo "</table></div>";
									}
								}
								echo "</form>";
								echo "<div class='VideoBottom'><span class='link' onclick='confirmSubmit(\"".$Video->getID()."\");'>Delete Selected</span></div>";
							}
							echo "</div>";
						}
					} else {

						echo "<div class='VideoBox'>";	
						echo "<div class='VideoHeadline'>Welcome</div>";
						echo "<div class='VideoSubtitle'>Please select a library.";
						if($FolderCheck === true) {
							//echo "<br>Please check the path to your PlexMediaFolder in the settings.php or install the Devtools.Bundle by Dane22 on the Plex Forums.";
							echo "One or more errors has occured:<br>";
							foreach($LogArray['error'] as $LogEntry) {
								echo $LogEntry . "<br>";
							}
						}
						echo "</div>";
						echo "</div>";
					}
if($Debug) {
	echo "<div class='VideoBox'>";	
	echo "<div class='VideoHeadline'>Debug</div>";
	echo "<div class='VideoSubtitle'>";
	foreach($LogArray['debug'] as $LogEntry) {
		echo $LogEntry . "<br>";
	}
	echo "</div>";
	echo "</div>";
}
					?>
				</div>
				</td></tr></table>
		<iframe name="WorkFrame" class="hiddenframe"></iframe>
	</body>
</html>