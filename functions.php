<?php
/**
* This file contains the functions used by the PlexUSM
*/

/**
 * Custom sort function for sorting videos based on title.
 */
function SortVideos( $a, $b ) {
	if( ($a->getRatingKey()>0) and ($b->getRatingKey()>0) ){
		$a = $a->getRatingKey() . "." . $a->getSeasonIndex() . ".". $a->getEpisodeIndex();
		$b = $b->getRatingKey() . "." . $b->getSeasonIndex() . ".". $b->getEpisodeIndex();	

	} elseif( ($a->getEpisodeIndex()>0) and ($b->getEpisodeIndex()>0) ){
		$a = $a->getSeasonIndex() . ".". $a->getEpisodeIndex();
		$b = $b->getSeasonIndex() . ".". $b->getEpisodeIndex();

	} else {
		$a = strtolower($a->getTitle());
		$b = strtolower($b->getTitle());		
	}
	return strnatcmp($a,$b);
}



function get_show_seasons($ShowKey) {
	global $Server, $ArrayVideos;
	//$xmlsub = simplexml_load_file($Server . $ShowKey . '/all');
	$xmlsub = FetchXML($ShowKey . '/all');
	foreach($xmlsub as $xmlrowsub) {
		$Season = $xmlrowsub->attributes();
		$CurrentVideo = new Video($Season->key,$Season->title);
		USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Found season: '" . $Season->title ."'");
		$CurrentVideo->setType("show");
		$CurrentVideo->setParentID($ShowKey);
		$CurrentVideo->setLibraryID($_GET['libraryID']);
		$CurrentVideo->setEpisodeIndex(0);
		$ArrayVideos[] = $CurrentVideo;	
	}
}

function get_show_episodes($ShowKey, $SeasonIndex = false, $ShowRatingKey = "", $SearchString = false) {
	global $Server, $ArrayVideos, $PathToPlexMediaFolder, $SearchSubtitleProviderFiles;
	$MatchedEpisodes = false;

	//$xmlsub = simplexml_load_file($Server.$ShowKey);
	$xmlsub = FetchXML($ShowKey);
	foreach($xmlsub as $xmlrowsub) {
		$AddVideo = true;
		//var_dump($xmlrowsub2);

		$Episode = $xmlrowsub->attributes();
		USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Found episode: '" . $Episode->title ."'");
		
		$CurrentVideo = new Video($Episode->key,$Episode->title);
		$CurrentVideo->setType("movie");
		$CurrentVideo->setEpisodeIndex($Episode->index);
		
		if($SearchString !== false){
			if(stripos($CurrentVideo->getTitle(),$SearchString) === false) {
				continue;
			}
			$MatchedEpisodes = true;

		}
		/**
			 * Get Season Number
			 */
		if($SeasonIndex !== false ) {
			$CurrentVideo->setSeasonIndex($SeasonIndex->index);
			$CurrentVideo->setTitleShow($ShowRatingKey->getTitle());
			$CurrentVideo->setTitleSeason($SeasonIndex->title);
			$CurrentVideo->setRatingKey($ShowRatingKey->getRatingKey());
		} else {
			//$Season_XML = simplexml_load_file($Server . $Episode->parentKey . '/tree');
			
			$Season_XML = FetchXML($Episode->parentKey);
			foreach($Season_XML as $Season) {
				
				if((int)$Season->attributes()->ratingKey == (int)$Episode->parentRatingKey) {
					$CurrentVideo->setSeasonIndex($Season->attributes()->index);
					$CurrentVideo->setTitleSeason($Season->attributes()->title);
					$CurrentVideo->setSeasonKey($Season->attributes()->key);
					$CurrentVideo->setRatingKey($Season->attributes()->ratingKey);
					$CurrentVideo->setTitleShow($Season->attributes()->parentTitle);
					$CurrentVideo->setShowKey($Season->attributes()->parentKey);
				}
			}
						
		}
		
		//$ActiveSubtitleXML = simplexml_load_file($Server.$Episode->key);
		$ActiveSubtitleXML = FetchXML($Episode->key);
		foreach($ActiveSubtitleXML as $ActiveSubtitle) { 
			$Streams = $ActiveSubtitle->Media->Part->Stream;
			foreach($Streams as $ActiveSubtitle) {
				if( ($ActiveSubtitle->attributes()->streamType == 3) and (isset($ActiveSubtitle->attributes()->selected)) ) {
					$CurrentVideo->setActiveSubtitle($ActiveSubtitle->attributes()->id);
				}
			}	
		}

		//$xmlsub3 = simplexml_load_file($Server.$Episode->key . '/tree');
		$xmlsub3 = FetchXML($Episode->key . '/tree');
		foreach($xmlsub3 as $xmlrowsub3) {
			$CurrentMediaPart= $xmlrowsub3->MetadataItem->MetadataItem->MediaItem->MediaPart;
			$CurrentVideo->setPath($CurrentMediaPart->attributes()->file);
			$CurrentVideo->setHash($CurrentMediaPart->attributes()->hash);
			foreach($CurrentMediaPart->MediaStream as $Subtitle) {
				if($Subtitle->attributes()->type == 3) {

					// Filter out VOBSUB by checking that there is a url connected to the subtitle.
					$Language = "-";
					if(strlen($Subtitle->attributes()->language)>0) {
						$Language = $Subtitle->attributes()->language;
					}					
					if(isset($Subtitle->attributes()->url)) {
						$LocalSubtitle = false;
						$Folder = SepFilename(preg_replace("/\\\\/i", "/", $Subtitle->attributes()->url));

						// Subtitles - All of them

						if(strpos($Folder[0],"media://")!==false) {
							$Folder[0] = $PathToPlexMediaFolder . substr($Folder[0],8);
						} else {
							$LocalSubtitle = true;
						}
						if($_SESSION['Option_HideLocal']['set'] === false) { 
							$CurrentVideo->setNewSubtitle(new Subtitle($Subtitle->attributes()->id, $Folder[1], $Language, $Folder[0] . "/" . $Folder[1], $Subtitle->attributes()->codec));
							USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Found subtitle: '" . $Folder[0] . "/" . $Folder[1] ."'");
						} else {
							if($LocalSubtitle === false) {
								$CurrentVideo->setNewSubtitle(new Subtitle($Subtitle->attributes()->id, $Folder[1], $Language, $Folder[0] . "/" . $Folder[1], $Subtitle->attributes()->codec));
								USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Found subtitle: '" . $Folder[0] . "/" . $Folder[1] ."'");
							}
						}
					} else {
						if($_SESSION['Option_HideIntegrated']['set']  === false) {
							$CurrentVideo->setNewSubtitle(new Subtitle($Subtitle->attributes()->id, "Integrated subtitle", $Language,  false, $Subtitle->attributes()->codec));	
							
						}
					}
				}
			}
		}
		
		/** Check if there is duplicates according to .xml file in Subtitle Contributions.
		
		foreach ($SearchSubtitleProviderFiles as $Provider) {
			$HashDirectory = substr($CurrentVideo->getHash(),0,1) . "/" . substr($CurrentVideo->getHash(),1) . ".bundle/Contents/Subtitle Contributions/" . $Provider;
			if(file_exists($PathToPlexMediaFolder . $HashDirectory)) {
				//echo "Exists(URL: " . $PathToPlexMediaFolder . $HashDirectory . ")<br>";
				$SubtitleProviderXML = simplexml_load_file($PathToPlexMediaFolder . $HashDirectory);
				foreach($SubtitleProviderXML as $SubtitleProvider) {
					foreach($SubtitleProvider->Subtitle as $Sub) {
					echo $Sub->attributes()->name;
					
					echo "<br>";
					}
					//echo $SubtitleProvider->Subtitle->attributes()->media;
					echo "<br>";echo "<br>";
					
				}
			}
		}
		*/
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
	if($SearchString !== false) {
		return $MatchedEpisodes;
	}
}

function CheckSettings() {
	global $Server, $PathToPlexMediaFolder, $AppendPathWith, $Debug;
	$ErrorOccured = false;
	
	if(!extension_loaded('simplexml')) {
		USMLog("error", "The extension 'SimpleXML' is not loaded.");
		$ErrorOccured = true;
	}

	if(!ini_get('allow_url_fopen')) {
		USMLog("error", "allow_url_fopen is set to false. Needs to be enabled to allow opening of url:s.");
		$ErrorOccured = true;
	}
	
	if(!$ErrorOccured) {
		$Settings = FetchXML("/:/plugins/com.plexapp.plugins.DevTools/prefs");
		//$Settings = simplexml_load_file($Server. "/:/plugins/com.plexapp.plugins.DevTools/prefs");
		if($Settings !== false) {
			foreach($Settings as $SettingsItem) {

				if($SettingsItem->attributes()->id == "Home") {
					$PathToPlexMediaFolder = preg_replace("/\\\\/i", "/", $SettingsItem->attributes()->value) . $AppendPathWith;
				}
			}
		}
		if($Debug) {
			USMLog("debug", "Value of PathToPlexMediaFolder:'" . $PathToPlexMediaFolder ."'", debug_backtrace());
		}	

		if(file_exists($PathToPlexMediaFolder) === false) {
			USMLog("error", "The path: '" . $PathToPlexMediaFolder . "' does not exist.");
			$ErrorOccured = true;
		}
		
		USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] PathToPlexMediaFolder set to: '" . $PathToPlexMediaFolder ."'");
	}

	
	return $ErrorOccured;	
}


function FetchXML ($url) {
	global $Server, $Debug;
	$ErrorOccured = false;
	$xmlResult = "";
	$xmlResult = simplexml_load_file($Server . $url);
	if(!$xmlResult) {
		USMLog("error", "Failed to fetch xml from path: '" . $Server . $url."'");
		$ErrorOccured = true;	
	}
	
	if($Debug) {
		USMLog("debug", "Received request to fetch xml from: '" . $Server . $url."'", debug_backtrace());
	}
	
	if(!$ErrorOccured) {
		if($Debug) {
			USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Contents in xmlResult: \n" . var_export($xmlResult, true) ."\n");
		}
		USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Successfully received XML from '" . $Server . $url."'");
		return $xmlResult;
	} else {
		return false;
	}
}

function USMLog ($Type, $Message, $Debugtrace = false) {
	global $Logfile;
	$LogEntry = date("y-m-d H:i:s") . " [".$Type . "] " . $Message;
	$_SESSION['Log'][$Type][] = $LogEntry;

	$fp = fopen($Logfile, 'a');
	fwrite($fp, $LogEntry);
	
	
	if($Debugtrace !== false) {
		$DebugMessage = "Debug backtrace:\n";
		for($i=0;$i<count($Debugtrace);$i++) {
			$DebugMessage .= "[".$i."] Function name: " . $Debugtrace[$i]['function'] . "\n";
			$DebugMessage .= "[".$i."] Line number: " . $Debugtrace[$i]['line'] ." in file ". $Debugtrace[$i]['file'] . "\n";	
			$DebugMessage .= "[".$i."] Arguments: " . var_export($Debugtrace[$i]['args'],true) . "\n";	
		}			
		$LogEntry = date("y-m-d H:i:s") . " [".$Type . "] " . $DebugMessage;
		$_SESSION['Log'][$Type][] = $LogEntry;
		fwrite($fp, $LogEntry);
	}
	
	fclose($fp);	
}
// check if com.plexapp.agents.opensubtitles.xml or com.plexapp.agents.podnapisi.xml exists in Hash/Contents/Subtitle Contributions/ if so, parse the hell out of it.
// check if subtitle->name contains /sid- (atleast for opensubtitles. Check if this applies to podnapasi aswell)
?>