<?php
/**
* This file contains the functions used by the PlexUSM
*/

/**
 * This function splits a filename and returns the filename and path in an array.
 * Updated to a more efficient version.
 */
function SepFilename($filename) {
	$filename = preg_replace("/\\\\/i", "/", $filename);
	$name[0] = substr($filename,0,strripos($filename,"/")+1);
	$name[1] = substr($filename,strripos($filename,"/")+1);
	
	return $name;
}

/**
 * Custom sort function for sorting videos based on different criteria.
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


/**
 * This function fetches the seasons that belong to the ShowKey (/library/metadata/xxxxx/children) provided.
 * It then populates the globally used $ArrayVideos with the found season.
 */
function get_show_seasons($ShowKey) {
	global $Server, $ArrayVideos, $CurrentLibraryID;
	
	$xmlsub = FetchXML($ShowKey . '/all');
	foreach($xmlsub as $xmlrowsub) {
		$Season = $xmlrowsub->attributes();
		$CurrentVideo = new Video($Season->key,$Season->title);
		USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Found season: '" . $Season->title ."'");
		$CurrentVideo->setType("show");
		$CurrentVideo->setParentID($ShowKey);
		$CurrentVideo->setLibraryID($CurrentLibraryID);
		$CurrentVideo->setEpisodeIndex(0);
		$ArrayVideos[] = $CurrentVideo;	
	}
}


/**
 * This functions lists all episodes for the provided show and season.
 */
function get_show_episodes($ShowKey, $SeasonIndex = false, $ShowRatingKey = "", $SearchString = false) {
	global $Server, $ArrayVideos, $PathToPlexMediaFolder, $SearchSubtitleProviderFiles;
	$MatchedEpisodes = false;
	
	$xmlsub = FetchXML($ShowKey);
	foreach($xmlsub as $xmlrowsub) {
		$AddVideo = true;
		
		$Episode = $xmlrowsub->attributes();
		USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Found episode: '" . $Episode->title ."'");
		
		$CurrentVideo = new Video($Episode->ratingKey,$Episode->title);
		$CurrentVideo->setType("movie");
		$CurrentVideo->setEpisodeIndex($Episode->index);
		
		if($SearchString !== false){
			if(stripos($CurrentVideo->getTitle(),$SearchString) === false) {
				continue;
			}
			$MatchedEpisodes = true;
		}
		
		/**
		 * Get some information about the season. Title, index(number) and RatingKey.
		 * A object can be provided if we are searching, but otherwise it's not provided.
		 */
		if($SeasonIndex !== false ) {
			$CurrentVideo->setSeasonIndex($SeasonIndex->index);
			$CurrentVideo->setTitleShow($ShowRatingKey->getTitle());
			$CurrentVideo->setTitleSeason($SeasonIndex->title);
			$CurrentVideo->setRatingKey($ShowRatingKey->getRatingKey());
		} else {
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
		
		/**
		 * Figure out what subtitle is selected in plex.
		 */
		$ActiveSubtitleXML = FetchXML($Episode->key);
		foreach($ActiveSubtitleXML as $ActiveSubtitle) { 
			$Streams = $ActiveSubtitle->Media->Part->Stream;
			foreach($Streams as $ActiveSubtitle) {
				if( ($ActiveSubtitle->attributes()->streamType == 3) and (isset($ActiveSubtitle->attributes()->selected)) ) {
					$CurrentVideo->setActiveSubtitle($ActiveSubtitle->attributes()->id);
				}
			}	
		}
		
		/**
		 * Fetch subtitles for  the current episode
		 */
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
					
					/**
					 * If url is set, it's a external subtitle. (agent or local).
					 * Else it's a integrated one.
					 */
					if(isset($Subtitle->attributes()->url)) {
						$LocalSubtitle = false;
						$Folder = SepFilename(preg_replace("/\\\\/i", "/", $Subtitle->attributes()->url));

						

						if(strpos($Folder[0],"media://")!==false) {
							$Folder[0] = $PathToPlexMediaFolder . substr($Folder[0],8);
						} else {
							$LocalSubtitle = true;
							
						}
						if($_SESSION['Option_HideLocal']['set'] === false) { 
							$CurrentVideo->setNewSubtitle(new Subtitle($Subtitle->attributes()->id, $Folder[1], $Language, $Folder[0] . $Folder[1], $Subtitle->attributes()->codec, $LocalSubtitle));
							USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Found subtitle: '" . $Folder[0] .  $Folder[1] ."'");
						} else {
							if($LocalSubtitle === false) {
								$CurrentVideo->setNewSubtitle(new Subtitle($Subtitle->attributes()->id, $Folder[1], $Language, $Folder[0]  . $Folder[1], $Subtitle->attributes()->codec, $LocalSubtitle));
								USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Found subtitle: '" . $Folder[0] . $Folder[1] ."'");
							}
						}
					} else {
						if($_SESSION['Option_HideIntegrated']['set']  === false) {
							$CurrentVideo->setNewSubtitle(new Subtitle($Subtitle->attributes()->id, "Integrated subtitle", $Language,  false, $Subtitle->attributes()->codec, $LocalSubtitle));	
							
						}
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
	if($SearchString !== false) {
		return $MatchedEpisodes;
	}
}

/**
 * Verify that we can access the server and that devtools is installed.
 * Also, verify that the PHP is configured right.
 */
function CheckSettings() {
	global $Server, $PathToPlexMediaFolder, $AppendPathWith, $Debug, $DevToolsSecret, $CorrectDevToolsVersion;
	$ErrorOccured = false;
	
	if(!extension_loaded('simplexml')) {
		USMLog("error", "The extension 'SimpleXML' is not loaded.");
		$ErrorOccured = true;
	}

	if(!ini_get('allow_url_fopen')) {
		USMLog("error", "allow_url_fopen is set to false. Needs to be enabled to allow opening of url:s.");
		$ErrorOccured = true;
	}
	
	/**
	 * Check that we can access the Plex Media Server
	 */
	if(VerifyHeader($Server . '/library/sections', "200") === false) {
	USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Unable to access Plex Media Server on: " . $Server . ".");
		return true;
	}
	
	/**
	 * Check version of DevTools and that it is installed.
	 */
	$PathToDevToolsVersoin = $Server . "/utils/devtools?Func=GetVersion&Secret=" . $DevToolsSecret;
	$DevToolsVersion = file_get_contents($PathToDevToolsVersoin);
	
	
	if( ($DevToolsVersion === false) or (VerifyHeader($PathToDevToolsVersoin,"200") === false) ) {
		USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Found no DevTools. Please download from Plex Forums or Unsupported Appstore.");
		$ErrorOccured = true;	
	} else {
		if($DevToolsVersion == "Error authenticating") {
			USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] DevTools returned authentication error. Please set the same secret in both Settings.php and DevTools preferences.");
			$ErrorOccured = true;
		} elseif(version_compare($DevToolsVersion,$CorrectDevToolsVersion,">=")) {
			USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Found correct DevTools. Found: [". $DevToolsVersion . "]");
		} else{
			/* Add Stylesheet on this output */
			USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Wrong DevTools found: Required: [" . $CorrectDevToolsVersion . "] Found: [". $DevToolsVersion . "] Please download from Plex Forums or Unsupported Appstore.");
			$ErrorOccured = true;
		}
	}
	
	if(!$ErrorOccured) {

		$PathToPlexMediaFolder = preg_replace("/\\\\/i", "/",file_get_contents($Server . "/utils/devtools?Func=GetLibPath&Secret=" . $DevToolsSecret)) . $AppendPathWith;
		if($PathToPlexMediaFolder !== false) {
			// Do a file_exists on the received path to verify it.
			USMLog("info", "[". __FILE__ ." Line:" . __LINE__ . "] Setting PathToPlexMediaFolder to: '" . $PathToPlexMediaFolder ."'");
		} else {
			USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Unable to get the path to Media folder: '" . $PathToPlexMediaFolder ."'");
		}
		
		if($Debug) {
			USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Value of PathToPlexMediaFolder:'" . $PathToPlexMediaFolder ."'", debug_backtrace());
		}	
		
		
		if(exists($PathToPlexMediaFolder) === false) {
			USMLog("error", " The path: '" . $PathToPlexMediaFolder . "' does not exist.");
			$ErrorOccured = true;
		} else {
			USMLog("info", " The path: '" . $PathToPlexMediaFolder . "' is verified.");
		}
		
	}
	
	return $ErrorOccured;	
}

/**
 * Function for fetching the xmls.
 * This function checks to see that the result is correct.
 */
function FetchXML ($url) {
	global $Server, $Debug;
	$ErrorOccured = false;
	$xmlResult = "";
	$xmlResult = simplexml_load_file($Server . $url);
	if(!$xmlResult) {
		USMLog("error", "[". __FILE__ ." Line:" . __LINE__ . "] Failed to fetch xml from path: '" . $Server . $url."'");
		$ErrorOccured = true;	
	}
	
	if($Debug) {
		USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Received request to fetch xml from: '" . $Server . $url."'", debug_backtrace());
	}
	
	if(!$ErrorOccured) {
		if($Debug) {
			USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Contents in xmlResult: \n" . var_export($xmlResult, true) ."\n");
		}
		USMLog("debug", "[". __FILE__ ." Line:" . __LINE__ . "] Successfully received XML from '" . $Server . $url."'");
		return $xmlResult;
	} else {
		return false;
	}
}

/**
 * Log function.
 */
function USMLog ($Type, $Message, $Debugtrace = false) {
	global $Logfile;
	$Timestamp = date("y-m-d H:i:s");
	$LogEntry = $Timestamp . " [".$Type . "] " . $Message;
	$_SESSION['Log'][$Type][] = $Timestamp . " [".$Type . "] " . substr($Message,strpos($Message,"]")+1);

	$fp = fopen($Logfile, 'a');
	fwrite($fp, $LogEntry . "\n");
	
	
	if($Debugtrace !== false) {
		$DebugMessage = "Debug backtrace:\n";
		for($i=0;$i<count($Debugtrace);$i++) {
			$DebugMessage .= "[".$i."] Function name: " . $Debugtrace[$i]['function'] . "\n";
			$DebugMessage .= "[".$i."] Line number: " . $Debugtrace[$i]['line'] ." in file ". $Debugtrace[$i]['file'] . "\n";	
			$DebugMessage .= "[".$i."] Arguments: " . var_export($Debugtrace[$i]['args'],true) . "\n";	
		}			
		$LogEntry = date("y-m-d H:i:s") . " [".$Type . "] " . $DebugMessage;
		$_SESSION['Log'][$Type][] = $LogEntry;
		fwrite($fp, $LogEntry . "\n");
	}
	
	fclose($fp);	
}

/**
 * Checks to see if a file or folder exists using DevTools.
 * This means the check is done with the permissions Plex has.
 */
function exists($Path) {
	global $Server, $DevToolsSecret;
	
	if(strpos($Path,"file://")!==false) {
		$Path = substr($Path,7);
	}
	$ReturnValue = file_get_contents($Server . "/utils/devtools?Func=PathExists&Secret=" . $DevToolsSecret . "&Path=".preg_replace("/ /", "%20", $Path));
	
	if( ($ReturnValue == "false") or ($ReturnValue === false) ) {
		return false;
	} else {
		return $ReturnValue;
	}
}


/**
 * This function checks what headers are returned when quering a url. It then checks for the requested response code.
 */
function VerifyHeader($url,$response_code) {
	$headers = get_headers($url);
	if(strpos($headers[0],$response_code) === false) {
		return false;
	} else {
		return true;
	}
}
// check if com.plexapp.agents.opensubtitles.xml or com.plexapp.agents.podnapisi.xml exists in Hash/Contents/Subtitle Contributions/ if so, parse the hell out of it.
// check if subtitle->name contains /sid- (atleast for opensubtitles. Check if this applies to podnapasi aswell)


function CheckForDuplicates($CurrentVideo) {
	global $SearchSubtitleProviderFiles, $PathToPlexMediaFolder, $DevToolsSecret;
	//Check if there is duplicates according to .xml file in Subtitle Contributions.
	$ProviderXMLSubtitles = array();
	foreach ($SearchSubtitleProviderFiles as $Provider) {
		$HashDirectory = substr($CurrentVideo->getHash(),0,1) . "/" . substr($CurrentVideo->getHash(),1) . ".bundle/Contents/Subtitle Contributions/" . $Provider;
		if(exists($PathToPlexMediaFolder . $HashDirectory)) {
			
			$SubtitleProviderXML = FetchXML("/utils/devtools?Func=GetXMLFile&Secret=".$DevToolsSecret."&Path=".$PathToPlexMediaFolder . $HashDirectory);
			foreach($SubtitleProviderXML as $SubtitleProvider) {
				foreach($SubtitleProvider->Subtitle as $Sub) {
					
					if(strlen((string)$Sub->attributes()->name)>0) {
						$PositionOfSid = stripos((string)$Sub->attributes()->name,"/sid");
						
						$InsertTo = count($ProviderXMLSubtitles);
						if($PositionOfSid === false) {
							
							$ProviderXMLSubtitles[$InsertTo][0] = (string)$Sub->attributes()->name;
						} else {
							$name = substr((string)$Sub->attributes()->name,0,$PositionOfSid);
							$ProviderXMLSubtitles[$InsertTo][0] = $name;
						}
						$ProviderXMLSubtitles[$InsertTo][1] = substr($Provider,0,-4) . "_" . (string)$Sub->attributes()->media;
					}
				
				}
				
				

			}
		}
	}
	
	for ($i=0; $i<count($ProviderXMLSubtitles);$i++) {
		for ($x=0;$x<count($ProviderXMLSubtitles);$x++) {
			if($i != $x) {
				if ($ProviderXMLSubtitles[$i][0] == $ProviderXMLSubtitles[$x][0]) {
					foreach ($CurrentVideo->getSubtitles() as $SubtitleLanguageArray) {
						foreach ($SubtitleLanguageArray as $Subtitle) {
							if($ProviderXMLSubtitles[$i][1] == $Subtitle->getFilename()) {
								$Subtitle->setIsDouble(true);
							}
						}
					}
					
				}
			}
		}
	}					
}
?>