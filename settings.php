<?php
/**
 * Settings
 * The PathToPlexMediaFolder will be overwritten if the Devtools.bundle is available in your plugins directory as the script fetches this information from that plugin instead.
 */
set_time_limit(0);
$Username = "l-admin";
$PathToPlexMediaFolder = "C:/users/".$Username."/AppData/Local/Plex Media Server/Media/Localhost/";
$AppendPathWith = "/Media/Localhost/";

$Server = "http://localhost:32400";
$ItemsPerPage = 5;
$Logfile = "plexusmerror.log";
$LogArray = array();
$Debug = true; // use this to force logging on always and more detailed.
?>