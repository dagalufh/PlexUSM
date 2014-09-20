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
	$xmlsub = simplexml_load_file($Server . $ShowKey . '/all');
	foreach($xmlsub as $xmlrowsub) {
		$Season = $xmlrowsub->attributes();
		$CurrentVideo = new Video($Season->key,$Season->title);
		$CurrentVideo->setType("show");
		$CurrentVideo->setParentID($ShowKey);
		$CurrentVideo->setLibraryID($_GET['libraryID']);
		$CurrentVideo->setEpisodeIndex(0);
		$ArrayVideos[] = $CurrentVideo;	
	}
}

function get_show_episodes($ShowKey, $SeasonIndex = false, $ShowRatingKey = "", $SearchString = false) {
	global $Server, $ArrayVideos, $PathToPlexMediaFolder;
	$MatchedEpisodes = false;

	$xmlsub = simplexml_load_file($Server.$ShowKey);
	foreach($xmlsub as $xmlrowsub) {
		$AddVideo = true;
		//var_dump($xmlrowsub2);

		$Episode = $xmlrowsub->attributes();
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
			$Season_XML = simplexml_load_file($Server . $Episode->parentKey . '/tree');
			foreach($Season_XML as $Season) {
				if($Season->attributes()->key == $Episode->parentkey) {
					$CurrentVideo->setSeasonIndex($Season->attributes()->index);
					
					$Show_XML = simplexml_load_file($Server . $Episode->parentKey . '/tree');
					foreach($Show_XML as $Show) {
						if($Show->attributes()->key == $Season->parentkey) {
							$CurrentVideo->setRatingKey($Show->attributes()->ratingKey);
							
						}
					}
				}
			}		
		}
		
		$ActiveSubtitleXML = simplexml_load_file($Server.$Episode->key);
		foreach($ActiveSubtitleXML as $ActiveSubtitle) { 
			$Streams = $ActiveSubtitle->Media->Part->Stream;
			foreach($Streams as $ActiveSubtitle) {
				if( ($ActiveSubtitle->attributes()->streamType == 3) and (isset($ActiveSubtitle->attributes()->selected)) ) {
					$CurrentVideo->setActiveSubtitle($ActiveSubtitle->attributes()->id);
				}
			}	
		}

		$xmlsub3 = simplexml_load_file($Server.$Episode->key . '/tree');
		foreach($xmlsub3 as $xmlrowsub3) {
			$CurrentMediaPart= $xmlrowsub3->MetadataItem->MetadataItem->MediaItem->MediaPart;
			
			$CurrentVideo->setPath($CurrentMediaPart->attributes()->file);
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
						} else {
							if($LocalSubtitle === false) {
								$CurrentVideo->setNewSubtitle(new Subtitle($Subtitle->attributes()->id, $Folder[1], $Language, $Folder[0] . "/" . $Folder[1], $Subtitle->attributes()->codec));
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
	global $Server, $PathToPlexMediaFolder, $AppendPathWith;
	$Settings = simplexml_load_file($Server. "/:/plugins/com.plexapp.plugins.DevTools/prefs");
	foreach($Settings as $SettingsItem) {
		
		if($SettingsItem->attributes()->id == "Home") {
			$PathToPlexMediaFolder = preg_replace("/\\\\/i", "/", $SettingsItem->attributes()->value) . $AppendPathWith;
		}
	}
	
	if(file_exists($PathToPlexMediaFolder) === false) {
		return false;
	}
}
?>