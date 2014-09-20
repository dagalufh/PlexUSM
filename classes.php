<?php
/**
 * Define a class to be used for subtitles?
 */ 
class Video {
	private $ActiveSubtitle;
	private $ArraySubtitles = array();
	private $EpisodeIndex;
	private $Hash;
	private $ID;
	private $LibraryID;
	private $LibraryType;
	private $NumberOfChilds;
	private $ParentID;
	private $Path;
	private $RatingKey;
	private $SeasonIndex;
	private $Title; 	 	
	private $TitleShow;
	private $TitleSeason;
	private $Type;
	
	function __construct($ID,$Title) {
		$this->ID = $ID;
		$this->Title = $Title;
	}

	public function getActiveSubtitle() {
		return $this->ActiveSubtitle;
	}
	
	public function getEpisodeIndex() {
		return $this->EpisodeIndex;
	}

	public function getHash() {
		return $this->Hash;
	}

	public function getID() {
		return $this->ID;	
	}

	public function getLibraryID() {
		return $this->LibraryID;	
	}

	public function getLibraryType() {
		return $this->LibraryType;	
	}

	public function getNumberOfChilds() {
		return $this->NumberOfChilds;	
	}

	public function getPath() {
		return $this->Path;	
	}

	public function getParentID() {
		return $this->ParentID;	
	}

	public function getRatingKey() {
		return $this->RatingKey;
	} 

	public function getSubtitles() {
		return $this->ArraySubtitles;
	}

	public function getSeasonIndex() {
		return $this->SeasonIndex;
	}

	public function getTitle() {
		return $this->Title;
	}
	
	public function getTitleShow() {
		return $this->TitleShow;
	}

	public function getTitleSeason() {
		return $this->TitleSeason;
	}	

	public function getType() {
		return $this->Type;
	}

	// Set-functions
	public function setActiveSubtitle($SubtitleID) {
		$this->ActiveSubtitle = $SubtitleID;
	}
	public function setEpisodeIndex($Index) {
		$this->EpisodeIndex = $Index;
	}

	public function setHash($Hash) {
		$this->Hash = $Hash;	
	}

	public function setLibraryID($LibraryID) {
		$this->LibraryID = $LibraryID;
	}

	public function setLibraryType($LibraryType) {
		$this->LibraryType = $LibraryType;
	}

	public function setNewSubtitle($Subtitle) {
		$this->ArraySubtitles[(string)$Subtitle->getLanguage()][] = $Subtitle;
	}

	public function setNumberOfChilds($NumberOfChilds) {
		$this->NumberOfChilds = $NumberOfChilds;	
	}

	public function setPath ($Path) {
		$this->Path = $Path;
	}

	public function setParentID ($ParentID) {
		$this->ParentID = $ParentID;	
	}

	public function setRatingKey ($RatingKey) {
		$this->RatingKey = $RatingKey;
	}

	public function setSeasonIndex($Index) {
		$this->SeasonIndex = $Index;
	}
	
	public function setTitleShow($TitleShow) {
		$this->TitleShow = $TitleShow;
	}

	public function setTitleSeason($TitleSeason) {
		$this->TitleSeason = $TitleSeason;
	}
	
	public function setType ($Type) {
		$this->Type = $Type;
	}
}

class Subtitle {
	private $Filename;
	private $ID;
	private $Language;
	private $Path;
	private $Source;

	function __construct($ID, $Filename, $Language,$Path,$Source) {
		$this->Filename = $Filename;
		$this->ID = $ID;
		$this->Language = $Language;
		$this->Path = $Path;
		$this->Source = $Source;
	}

	public function getFilename() {
		return $this->Filename;
	}
	
	public function getID() {
		return $this->ID;
	}

	public function getLanguage() {
		return $this->Language;	
	}

	public function getPath() {
		return $this->Path;
	}

	public function getSource() {
		return $this->Source;	
	}
}
?>