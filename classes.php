<?php
/**
 * Define a class to be used for subtitles?
 */ 
 class Video {
 	private $ArraySubtitles = array();
 	private $ID;
 	private $LibraryID;
 	private $LibraryType;
 	private $NumberOfChilds;
 	private $Title; 	 	
 	private $ParentID;
 	private $Path;
 	private $Hash;
 	
 	function __construct($ID,$Title) {
 		$this->ID = $ID;
 		$this->Title = $Title;
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
 	
 	public function getSubtitles() {
 		return $this->ArraySubtitles;
 	}
 	
 	public function getTitle() {
 		return $this->Title;
 	}
 	
 	public function setNewSubtitle($Subtitle) {
 			$this->ArraySubtitles[$Subtitle->getLanguage()][] = $Subtitle;
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
 	
 	public function setNumberOfChilds($NumberOfChilds) {
 		$this->NumberOfChilds = $NumberOfChilds;	
 	}
 	
 	public function setPath ($Path) {
 		$this->Path = $Path;
 	}
 	
 	public function setParentID ($ParentID) {
 		$this->ParentID = $ParentID;	
 	}
 }
 
 class Subtitle {
 	private $Filename;
 	private $Language;
 	private $Path;
 	private $Source;
 	
 	function __construct($Filename, $Language,$Path,$Source) {
 		$this->Filename = $Filename;
 		$this->Language = $Language;
 		$this->Path = $Path;
 		$this->Source = $Source;
 	}
 	
 	public function getFilename() {
 		return $this->Filename;
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