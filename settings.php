<?php
/**
 * Settings
 * The PathToPlexMediaFolder will be overwritten if the Devtools.bundle is available in your plugins directory as the script fetches this information from that plugin instead.
 */
set_time_limit(60);
$Username = "Your Windows Account Name";
$PathToPlexMediaFolder = "C:/users/".$Username."/AppData/Local/Plex Media Server/Media/Localhost/";
$AppendPathWith = "/Media/localhost/";


// Please do not change this to localhost as that slows the script down massivly
$Server = "http://127.0.0.1:32400";
$ItemsPerPage = 5;
$Logfile = "plexusmerror.log";
$LogArray = array();
$Debug = false; // use this to force logging on always and more detailed.
?>