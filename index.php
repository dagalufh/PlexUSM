<?php
session_start();
$starttime = microtime();
$startarray = explode(" ", $starttime);
$starttime = $startarray[1] + $startarray[0];
/**
 * Created by Mikael Aspehed (dagalufh on PlexForums)
 * With assistance from Dane22 on PlexForums and his plugin DevTools
 * 
 * Changelog
 * 
 * V0.5.4
 * Changed requirements to 0.0.0.8 of DevTools
 * Changed DelSRT to DelSub in DevTools to use current API.
 * Added force refresh link to top of page when listing section.
 *  
 * V0.5.3
 * Changed requirements to 0.0.0.7 of DevTools
 * Changed subtitle removal function to DelSRT of DevTools.
 * Changed recommended way of usage.
 * Cleaned up some variables that were unused.
 * 
 * V0.5.2
 * Added more rigid verification that DevTools is infact there.
 * Corrected a bug with "Show only multiple subtitles/language" that caused it to hide entire video.
 * Added some more comments.
 * Added a new option: Autoselect duplicates based on XML. This goes through the providers xml file and searches for duplicates base on their name in the file.
 *
 * 
 * V0.5.1
 * Added Select / Deselect all to subtitlelists.
 * Changed requirements to DevTools v0.0.0.6 after implementing ShowSRT function that uses DevTools to read the file.
 * Removed "ListDir.php" as it's not needed anymore.
 * Modified "ReadFile.php" to fetch the file to be viewed from DevTools instead of accessing it directly to make it work on linux etc.
 *
 * V0.5.0
 * Moved away from features that required PHP to have access to other places on your harddrive.
 * These features are handled by DevTools from now on. (Deletion of files, file exists)
 * Currently requires DevTools v.0.0.0.5 made by Dane22 on the Plex Forums
 * Modified the settings.php do reflect this change. Now only contains 3 settings that a user should fiddle with.
 * Continued in making the presentation better.
 * Improved the logging and presentation of the same.
 *
 * V0.4.2
 * Added some logging
 * Corrected in Settings.php to append with localhost, with a lowercase "l".
 *
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
include("classes.php");
include("settings.php");
include("functions.php");

/**
 * Check settings to verify all is correct
 */ 
$SettingsVerification = CheckSettings();

/**
 * Define some variables used.
 */
$ArrayVideos = array();
$showID = false;
$Searchstring = "";
$MenuItem = ""; // Holds the available pages output
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
	$_SESSION['Option_AutoCheck']['set'] = false;
	$_SESSION['Option_AutoCheck']['checked'] = "";		
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
	
	if(isset($_POST['AutoCheck'])) {
		$_SESSION['Option_AutoCheck']['set'] = true;
		$_SESSION['Option_AutoCheck']['checked'] = "checked";
	} else {
		$_SESSION['Option_AutoCheck']['set'] = false;
		$_SESSION['Option_AutoCheck']['checked'] = "";		
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
	$AdditionalLink_ShowKey = "&ShowKey=".$_GET['ShowKey'];
}

if( (isset($_GET['ParentKey'])) and (strlen($_GET['ParentKey'])>18) ) {
	$ParentKey= $_GET['ParentKey'];
	$AdditionalLink_ParentKey = "&ParentKey=".$_GET['ParentKey'];

}


/**
 *
 */
if( ($CurrentLibraryID !== false) and ($ErrorOccured === false) and (!$SettingsVerification)) {

	if( (isset($ShowKey)) and (!isset($ParentKey)) )  {
		get_show_seasons($ShowKey);	
	} elseif( (isset($ShowKey)) and (isset($ParentKey)) ) {
		get_show_episodes($ShowKey);
	} else {

		/** For the current section, list all the movies */
		$xmlsub = FetchXML('/library/sections/'.$CurrentLibraryID.'/all');
		foreach($xmlsub as $xmlrowsub) {	
			$AddVideo = true;

			$xmlsub2 = FetchXML($xmlrowsub['key'].'/tree');
			if ($xmlrowsub['type'] == "show") {
				/**
				 * If it is a show, we don't need to search for subtitles.
				 */
				if(strlen($Searchstring)>3){
					/**
				 * If it is a show, we don't need to search for subtitles.
				 */

					$CurrentVideo = new Video($xmlrowsub['key'],$xmlrowsub['title']);
					USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Found Show: '" . $xmlrowsub['title'] ."'");
					$CurrentVideo->setLibraryID($CurrentLibraryID);
					$CurrentVideo->setType($xmlrowsub['type']);
					$CurrentVideo->setRatingKey($xmlrowsub['ratingKey']);
					$CurrentVideo->setSeasonIndex('0');
					$CurrentVideo->setEpisodeIndex('0');
					$CurrentVideo->setActiveSubtitle(0);
					$MatchingEpisodes = false;

					$SeasonXML = FetchXML($CurrentVideo->getID() . '/all');
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
					$CurrentVideo->setLibraryID($CurrentLibraryID);
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
					USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Found Movie: '" . $xmlrowsub2->attributes()->title ."'");
					/**
					 * Check if we have a searchstring, and if so, check if the current title matches it.
					 */
					if(strlen($Searchstring)>3){
						if(stripos($CurrentVideo->getTitle(),$Searchstring) === false) {
							continue;
						}
					}

					$ActiveSubtitleXML = FetchXML($xmlrowsub['key']);
					foreach($ActiveSubtitleXML as $ActiveSubtitle) { 
						$Streams = $ActiveSubtitle->Media->Part->Stream;
						foreach($Streams as $ActiveSubtitle) {
							if( ($ActiveSubtitle->attributes()->streamType == 3) and (isset($ActiveSubtitle->attributes()->selected)) ) {
								$CurrentVideo->setActiveSubtitle($ActiveSubtitle->attributes()->id);
							}
						}	
					}


					$CurrentVideo->setLibraryID($CurrentLibraryID);
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
								$Folder = SepFilename($subtitle->attributes()->url);

								// Subtitles - All of them
								
								if(strpos($Folder[0],"media://")!==false) {
									$Folder[0] = $PathToPlexMediaFolder . substr($Folder[0],8);
								} else {
									$LocalSubtitle = true;
								}
								if($_SESSION['Option_HideLocal']['set'] === false) { 									
									$CurrentVideo->setNewSubtitle(new Subtitle($subtitle->attributes()->id, $Folder[1], $Language, $Folder[0] .  $Folder[1], $subtitle->attributes()->codec, $LocalSubtitle));
									USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Found subtitle: '" . $Folder[0] . $Folder[1] ."'");
								} else {
									if($LocalSubtitle === false) {
										$CurrentVideo->setNewSubtitle(new Subtitle($subtitle->attributes()->id, $Folder[1], $Language, $Folder[0] .  $Folder[1], $subtitle->attributes()->codec, $LocalSubtitle));
										USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Found subtitle: '" . $Folder[0] .  $Folder[1] ."'");
									}
								}

							} else {
								$LocalSubtitle = true;
								if($_SESSION['Option_HideIntegrated']['set']  === false) {
									$CurrentVideo->setNewSubtitle(new Subtitle($subtitle->attributes()->id, "Integrated subtitle", $Language,  false, $subtitle->attributes()->codec, $LocalSubtitle));	
								}
							}
						}
					}
					
					CheckForDuplicates($CurrentVideo);
					
					if ($_SESSION['Option_MultipleSubtitlesOnly']['set'] === true) {
						$AddVideo = false;
						foreach ($CurrentVideo->getSubtitles() as $SubtitleLanguageArray) {
							if (count($SubtitleLanguageArray)<2) {
								/* Current language has less than 2 subtitles */
								foreach ($SubtitleLanguageArray as $Subtitle) {
									$Subtitle->setHideSubtitle(true);
								}
							} else {
								$AddVideo = true;
							}
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
if( ($CurrentLibraryID !== false) and ($ErrorOccured === false) and (!$SettingsVerification)) {
	$RefreshLink = "<a target='WorkFrame' href='refresh.php?LibraryID=".$CurrentLibraryID."'>Refresh Section in Plex</a>";
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
		<script type="text/javascript">
			function confirmSubmit(formname, option)
			{
				var AnyBoxesChecked = false;
				checkboxes = document.forms[formname].getElementsByTagName('input');
				for (var i = 0; i < checkboxes.length; i++) {
					AnyBoxesChecked = checkboxes[i].checked	
					if(AnyBoxesChecked == true) { 
						break;
					}
				}
				if(AnyBoxesChecked == false) {
					return false;
				}
				
				var agree=confirm("Are you sure you wish to delete the selected subtitle(s)?");
				if (agree) {
					document.getElementById(formname).action = 'delete.php';
					document.getElementById(formname).submit();
				} else {
					return false ;
				}
			}
			
			function SelectDeselectAll(formname, toggle)
			{
				
				var checkboxes = new Array();
				checkboxes = document.forms[formname].getElementsByTagName('input');
				for (var i = 0; i < checkboxes.length; i++) {
					if (checkboxes[i].type === 'checkbox') {
						checkboxes[i].checked = toggle;
					}
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
echo "<div class='VideoBox'>";	
echo "<div class='VideoHeadline'>Libraries</div>";
echo "<div class='VideoSubtitle'>";
echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
if(!$SettingsVerification) {
	$xml = FetchXML('/library/sections');
	foreach($xml as $xmlrow) {
		$Section = $xmlrow->attributes();	
		if($Debug) {
			USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Value in Section-variable: \n" . var_export($Section,true) . "\n");
		}
		$LibraryName = $Section->title;
		if($Section->key == $CurrentLibraryID) {
			$CurrentLibraryName = $Section->title;
			$LibraryName = "<b>" . $Section->title . "</b>";
		}
		echo "<tr class='hovering'>";
		echo "<td class='mainText'><a href='?libraryID=".$Section->key."&startLimit=0'>".$LibraryName."</a></td></tr>";
	}
}
echo "</table>";
	echo "</div>";
	echo "</div>";


if ( ($CurrentLibraryID !== false) and (!$SettingsVerification) ) {
	echo "<form name='searchForm' method='GET' action=''>";
	echo "<div class='VideoBox'>";	
	echo "<div class='VideoHeadline'>Search: ".$CurrentLibraryName."</div>";
	echo "<div class='VideoSubtitle'>";	
	echo "<input type='hidden' name='libraryID' value='".$CurrentLibraryID."'>";
	echo "<input type='hidden' name='startLimit' value='0'>";
	echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
	echo "<tr><td class='mainText'>Enter title to search for:</td></tr>";
	echo "<tr><td class='mainText'><input type='text' name='searchCriteria' value='".$Searchstring."'></td></tr>";
	echo "<tr><td class='mainText'><input type='submit'></td></tr>";
	echo "</table>";
	echo "</div>";
	echo "</div>";
	echo "</form>";
	
}

echo "<form name='optionsForm' method='post' action=''>";
echo "<div class='VideoBox'>";	
echo "<div class='VideoHeadline'>Options</div>";
echo "<div class='VideoSubtitle'>";
echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
echo "<tr><td class='mainText'><input type='checkbox' name='HideLocal' ".$_SESSION['Option_HideLocal']['checked'].">Hide local subtitles</td></tr>";
echo "<tr><td class='mainText'><input type='checkbox' name='HideEmpty' ".$_SESSION['Option_HideEmpty']['checked'].">Hide videos without subtitles</td></tr>";	
echo "<tr><td class='mainText'><input type='checkbox' name='HideIntegrated' ".$_SESSION['Option_HideIntegrated']['checked'].">Hide integrated subtitles</td></tr>";
echo "<tr><td class='mainText'><input type='checkbox' name='OnlyMultiple' ".$_SESSION['Option_MultipleSubtitlesOnly']['checked'].">Show only multiple subtitles/language</td></tr>";
echo "<tr><td class='mainText'><input type='checkbox' name='AutoCheck' ".$_SESSION['Option_AutoCheck']['checked'].">Autoselect duplicates based on XML</td></tr>";
echo "<tr><td class='mainText'><input type='text' size='2' name='ItemsPerPage' value='".$_SESSION['Option_ItemsPerPage']['value']."'>Items per page</td></tr>";
echo "<tr><td class='mainText'><input type='submit' name='SaveOptions' value='Save'></td></tr>";
echo "</table>";
echo "</div>";
echo "</div>";
echo "</form>";
echo "<br>";
					?>
				</div>
				</td><td class="BoxTable_td">
				<div id="PageBar" class="headline"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><?php echo $MenuItem?></td><td class="Right"><?php echo $RefreshLink?></td></tr></table></div>
				<div id="MainBox">
					<?php	
						if( ($CurrentLibraryID !== false) and ($ErrorOccured === false) and (!$SettingsVerification) ){	
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
								echo "<div class='VideoHeadline'><a href='index.php?libraryID=".$CurrentLibraryID."&startLimit=0&ShowKey=".$Video->getID()."&ParentKey=".$Video->getParentID()."'>".$Video->getTitle()."</a></div>";
							} else {

								echo "<form id='Form_".(string)$Video->getID()."' name='Form_".(string)$Video->getID()."' method='POST' target='WorkFrame'>";

								if(strlen($Video->getTitleShow())>0) {
									$AdditionalShowOutput = "<span class='Shadow'>" .(string)$Video->getTitleShow() ."/". (string)$Video->getTitleSeason() . "/</span>";	
								}
								echo "<div class='VideoHeadline'>" . $AdditionalShowOutput . $Video->getTitle() . "</div>";
								echo "<div class='VideoPath'>".$Video->getPath()."</div>";
								
								/**
								 * Print out subtitles if it's not a show.
								 */ 
								foreach ($Video->getSubtitles() as $SubtitleLanguageArray) {	
									foreach ($SubtitleLanguageArray as $Subtitle) {
										/**
										 * Define default for each subtitle
										 */ 
										$Language = "";
										$Active = "";
										$View = "";
										$Checkbox = "";
										$Exists = "";
										$IsChecked = "";
										$AddClass = false;

										if(strlen($Subtitle->getLanguage())>0) {
											$Language =  $Subtitle->getLanguage();
										}


										if( ($Video->getActiveSubtitle()>0) and ((int)$Video->getActiveSubtitle() == (int)$Subtitle->getID()) ){
											$Active = "Selected subtitle in Plex";
											$AddClass = "Active";
										}
										
										if( ($Subtitle->getIsDouble()) and ($_SESSION['Option_AutoCheck']['set']) ){
											$IsChecked = "checked";	
										}

										if($Subtitle->getPath() !== false) {
											$View = "<a target=\"_NEW\" href=\"ReadFile.php?FileToOpen=".$Subtitle->getPath()."\">View</a> ";
											//$Checkbox = "<input type='checkbox' name='Subtitle[]' ". $IsChecked ." value=\"".$Subtitle->getPath()."\">";
											$Checkbox = "<input type='checkbox' name='Subtitle[]' ". $IsChecked ." value=\"".$Video->getID() . ":" . $Subtitle->getID().":" . $Subtitle->getFilename() ."\">";
										}
										if($Subtitle->getPath() !== false) {
											if(exists($Subtitle->getPath()) === false) {
												USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Unable to find '" . $Subtitle->getPath()."' on disk.");
												$Exists = "Not found on disk. Please update Plex library.";
												$Checkbox = "";
												$View = "";
												$AddClass = "Removed";
											}
										}
										
									
										
										if(!$Subtitle->getHideSubtitle()) {
											echo "<div class='VideoSubtitle hovering " . $AddClass . "'>";
											echo "<table class='Max' cellspacing=0 cellpadding=0><tr><td class='small'>" . $Checkbox . "</td><td class='small'>" . $Subtitle->getSource() . "</td><td class='small'>" .$Language ."</td><td>". $Subtitle->getFilename() . "</td><td class='small'>" . $View . "</td></tr>";
											if ($AddClass !== false) {
												echo "<tr><td></td><td></td><td></td><td>Message: " . $Active . $Exists . "</td></tr>";
											}
											echo "</table></div>";
										}
									}
								}
								echo "</form>";
								echo "<div class='VideoBottom'><span class='link' onclick=\"SelectDeselectAll('Form_".(string)$Video->getID()."', true)\">Select All</span> - <span class='link' onclick=\"SelectDeselectAll('Form_".(string)$Video->getID()."', false)\">Clear Selection</span> - <span class='link' onclick='confirmSubmit(\"Form_".$Video->getID()."\");'>Delete Selected</span></div>";
							}
							echo "</div>";
						}
					} else {

						echo "<div class='VideoBox'>";	
						echo "<div class='VideoHeadline'>Welcome</div>";
						echo "<div class='VideoSubtitle'>";
						echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
						echo "<tr><td class='mainText'>";
						echo "This is an unofficial manager for subtitles.<br>";
						echo "<b>Usage is on your own risk!</b><br><br>";
						echo "Current features:<br>";
						echo "<ul>";
						echo "<li>List all subtitles for a movie/tv show in your library. Both local and those downloaded by agents.</li>";
						echo "<li>View the subtitle and see it's contents to determine what to delete.</li>";
						echo "<li>Delete selected subtitle from the harddrive.</li>";
						echo "<li>Search for videos.</li>";
						echo "<li>Options for output.</li>";
						echo "<li>Highlighting active subtitle.</li>";
						echo "</ul>";
						echo "</td></tr>";
						echo "</table>";
						echo "</div></div>";
						echo "<div class='VideoBox'>";	
						echo "<div class='VideoHeadline'>Recommended usage</div>";
						echo "<div class='VideoSubtitle'>";
						echo "<table cellspacing=0 cellpadding=0 style='width: 100%'>";
						echo "<tr><td class='mainText'>";
						echo "<ol>";
						echo "<li>Remove the subtitles you want via this script.</li>";
						echo "<li>Do a forced refresh on Section level. Do not do this on video level. It will take a while for it to rescan everything if you have a large section. But the subtitle will be removed.</li>";
						echo "</ol>";
						echo "</td></tr>";
						echo "</table>";
						echo "</div></div>";
					}
if($Debug) {
	echo "<div class='VideoBox'>";	
	echo "<div class='VideoHeadline'>Debug</div>";
	echo "<div class='VideoSubtitle'>";
	foreach($_SESSION['Log']['debug'] as $LogEntry) {
		echo nl2br($LogEntry) . "<br>";
	}
	echo "</div>";
	echo "</div>";
}

if( (isset($_SESSION['Log']['error'])) and (count($_SESSION['Log']['error'])>0) ) {				
	echo "<div class='VideoBox'>";	
	echo "<div class='VideoHeadline'>Errorlog</div>";
	echo "<div class='VideoSubtitle Error'>";
	foreach($_SESSION['Log']['error'] as $LogEntry) {
		echo nl2br($LogEntry) . "<br>";
	}
	echo "</div>";
	echo "</div>";
}

echo "<div class='VideoBox'>";	
echo "<div class='VideoHeadline'>Infolog</div>";
echo "<div class='VideoSubtitle'>";
foreach($_SESSION['Log']['info'] as $LogEntry) {
		echo nl2br($LogEntry) . "<br>";
	}
	echo "</div>";

echo "</div>";

$endtime = microtime();
$endarray = explode(" ", $endtime);
$endtime = $endarray[1] + $endarray[0];
$totaltime = $endtime - $starttime;
$totaltime = round($totaltime,2);
echo "<div class='VideoBox'>";
echo "<div class='VideoSubtitle Center'>Page Loading Time: </b>" . $totaltime . " seconds.</div>";
echo "</div>";
/**
 * Empty the logs after they have been shown to the user.
 */
$_SESSION['Log'] = "";
					?>
				</div>
				</td></tr></table>
		<iframe name="WorkFrame" class="hiddenframe"></iframe>
	</body>
</html>