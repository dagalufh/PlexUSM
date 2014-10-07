<?php
/**
 * Define a class to be used for subtitles?
 */ 

/**
 * Class Video
 * Holds information related to a unique video.
 *
 * @since V0.2
 */
class Video {
	
	/**
	 * Holds the ID of the active subtitle in Plex.
	 * @var int $ActiveSubtitle
	 * $since V0.2
	 */
	private $ActiveSubtitle;
	
	/**
	 * Holds all the subtitles associated to this video
	 * @var array $ArraySubtitles
	 * @since V0.2
	 */ 
	private $ArraySubtitles = array();
	
	/**
	 * Holds the index of the episode. This is the number the episode has in the season.
	 * @var int $EpisodeIndex
	 * @since V0.2
	 */ 
	private $EpisodeIndex;
  
	/**
	 * Holds the hash of the video. this is used for determening the path of the subtitles.
  	 * @var string $Hash
  	 * @since V0.2
  	 */
	private $Hash;
	
	/**
	 * Holds the key of the video. 
	 * @var string $ID
	 * @since V0.2
	 */
	private $ID;
	
	/**
	 * Hold the ID of the section the video belongs to
	 * @var int $LibraryID
	 * @since V0.2
	 */ 
	private $LibraryID;
		
	/**
	 * Holds the amount of childs for the video. This is used for shows and seasons.
	 * @var int $NumberOfChilds
	 * @since V0.2
	 * @Deprecrated
	 */ 
	private $NumberOfChilds;
	
	/**
	 * Holds the Key of the parent.
	 * @var string $ParentID
	 * @since V0.2
	 */  
	private $ParentID;
	
	/**
	 * Holds the path to the videofile
	 * @var string $Path
	 * @since V0.2
	 */
	private $Path;
	
	/**
	 * Holds the ratingKey of the video. 
	 * Used for sorting eposides and seasons correctly for searching and listing.
	 * @var string $Key
	 * @since V0.2
	 */	
	private $RatingKey;
	
	/**
	 * Holds the index of the season, this is the season number
	 * @var int $SeasonIndex
	 * @since V0.2
	 */ 
	private $SeasonIndex;
	
	/**
	 * Holds the key of the season
	 * @var string $SeasonKey
	 * @since V0.2
	 */ 
	private $SeasonKey;
	
	/**
	 * Holds the key of the show that the video belongs to
	 * @var string $ShowKey
	 * @since V0.2
	 */ 
	private $ShowKey;
	
	/**
	 * Holds the title of the video/show/season
	 * @var string $Title
	 * @since V0.2
	 */ 
	private $Title; 
	
	/**
	 * Holds the title of the show the video belongs to
	 * @var string $TitleShow
	 * @since V0.2
	 */ 
	private $TitleShow;
	
	/**
	 * Holds the title of the season the video belongs to
	 * @var string $TitleSeason
	 * @since V0.2
	 */ 	
	private $TitleSeason;
	
	/**
	 * Holds the type of video. Movie, show, episode, season.
	 * @var string $Type
	 * @since V0.2
	 */ 
	private $Type;
	
	/**
	 * The construct takes the key and title of the video/show/season
	 * @var string $ID
	 * @var string $Title
	 * @since V0.2
	 */ 
	function __construct($ID, $Title) {
		$this->ID = $ID;
		$this->Title = $Title;
	}
	
	/***************************************************************************************************************
	 * GET - Functions
	 ***************************************************************************************************************/	

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

	/**
	 * @Deprecated
	public function getNumberOfChilds() {
		return $this->NumberOfChilds;	
	}
	*/

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
	
	public function getSeasonKey() {
		return $this->SeasonKey;
	}
	
	public function getShowKey() {
		return $this->ShowKey;
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

	/***************************************************************************************************************
	 * SET - Functions
	 ***************************************************************************************************************/
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

	public function setNewSubtitle($Subtitle) {
		$this->ArraySubtitles[(string)$Subtitle->getLanguage()][] = $Subtitle;
	}

	/**
	 * @Deprecated
	public function setNumberOfChilds($NumberOfChilds) {
		$this->NumberOfChilds = $NumberOfChilds;	
	}
	*/

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
	
	public function setSeasonKey($Key) {
		$this->SeasonKey = $Key;
	}
	
	public function setShowKey($Key) {
		$this->ShowKey = $Key;
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
	private $IsLocal;
	
	/**
	 * Holds if the subtitle is found to be a duplicate or not.
	 */
	 private $IsDouble;
	
	/**
	* Controls if the subtitles should be shown or not.
	* Default: false
	* @since V0.5.2
	*/
	private $HideSubtitle;

	function __construct($ID, $Filename, $Language,$Path,$Source, $IsLocal) {
		$this->Filename = $Filename;
		$this->ID = $ID;
		$this->Language = $Language;
		$this->Path = $Path;
		$this->Source = $Source;
		$this->IsLocal = $IsLocal;
		$this->HideSubtitle = false;
		$this->IsDouble = false;
	}

	public function getFilename() {
		return $this->Filename;
	}
	
	public function getID() {
		return $this->ID;
	}
	
	public function getIsDouble() {
		return $this->IsDouble;
	}
	
	public function getIsLocal() {
		return $this->IsLocal;
	}

	public function getLanguage() {
		return $this->Language;	
	}

	public function getPath() {
		return $this->Path;
	}
	
	/**
	 * Returns boolean true or false.
	 * @since V0.5.2
	 */
	public function getHideSubtitle() {
		return $this->HideSubtitle;
	}

	public function getSource() {
		return $this->Source;	
	}
	
	/**
	 * Sets the value to control if it should be shown or not.
	 * @param bool $Boolean true/false
	 * @since V0.5.2
	 */
	public function setHideSubtitle($Boolean) {
		$this->HideSubtitle = $Boolean;
	}
	
	public function setIsDouble($Boolean) {
		$this->IsDouble = $Boolean;
	}
}
?>