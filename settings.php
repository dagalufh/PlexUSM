<?php
/**
 * Settings
 * 
 */
set_time_limit(60);

/* Only modify the below 3 settings to match your setup.*/
$Server = "http://127.0.0.1:32400";
$DevToolsSecret = "WebtoolPUSM";
$ItemsPerPage = 5;



/**
 * Do not modify the below settings.
 */
$PathToPlexMediaFolder = "";
$AppendPathWith = "/Media/localhost/";
$CorrectDevToolsVersion = "0.0.0.5";
$Logfile = "plexusmerror.log";
$LogArray = array();
$Debug = false; // use this to force detailed logging.

$SearchSubtitleProviderFiles[0] = "com.plexapp.agents.opensubtitles.xml";
$SearchSubtitleProviderFiles[1] = "com.plexapp.agents.podnapisi.xml";
?>