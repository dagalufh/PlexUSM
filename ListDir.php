<?php

function ListDir($dir,$maxLevel) {

global $DirectoryContent;
unset($DirectoryContent);
$DirectoryContent = array();


if(!isset($maxLevel)) {
	$maxLevel=-1;
	}

	if(function_exists("getDir") === false) {
	function getDir($dir) {
		// Denna funktionen tar fram innehållet i medskickad mapp och lagrar dom i en sorterad array som returneras. Sorteringen är i fallande ordning.
		$directories = array();

		// Här kontrolleras för att säkerställa att medskickad adress är en mapp.
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {

				// För varje objekt i mappen.
				while (($file = readdir($dh)) !== false) {
			            if(!(($file == ".") or ($file == ".."))) {
			            	//Om det inte är en . eller .. mapp, lägg till den i arrayen.
			            	array_push($directories,$file);
			            }
			       }
			}
	   	}

	   	sort($directories); // Fallande sortering, 2009,2008 o.s.v.
	   	return $directories;
	}
	
	}

	if(function_exists("Listing") === false) {
	function Listing($dir,$levels,$maxLevel) {
		global $DirectoryContent;
		

		// Denna funktion används för att visa inläggen på själva sidan och visar från den mapp man skickar med och nedåt i trädet.
		if(is_dir($dir)) {
			//Om medskickad variabelvärde är en mapp exekveras denna del.

		 	$DirHandle = getDir($dir);
			foreach($DirHandle as $dh) {
					if(is_dir($dir . "/" . $dh)) {

						$DirectoryContent[count($DirectoryContent)] = $dir . "/" . $dh;
			            if(($levels < $maxLevel) or ($maxLevel < 0 )) {
			            array_merge($DirectoryContent,Listing($dir . "/" . $dh,$levels+1,$maxLevel));
			            }
						} else {
							$DirectoryContent[count($DirectoryContent)] = $dir . "/" . $dh;
							}
			}
		} else {
        $DirectoryContent[count($DirectoryContent)] = $dir;
		}
		return $DirectoryContent;
	}
	
	}
	
	
	$Temp = Listing($dir,0,$maxLevel);
	unset($GLOBALS['DirectoryContent']);
	return $Temp;
}

function ParseFilename($filename) {
	
	$filename = explode("/",$filename);
	$URL = "";
	$completeURL = "";
	for($i=0;$i<count($filename);$i++) {
		if(is_dir($URL . $filename[$i])) {
			$completeURL = $completeURL . "<a href='".$_SERVER['PHP_SELF']."?url=" . $URL . $filename[$i]."'>".$filename[$i]."</a>/";
			$URL = $URL . $filename[$i] . "/";
		} else {
			$completeURL = $completeURL . $filename[$i];
			}
	}
	return $completeURL;
}

function SepFilename($filename) {
	
	$filename = explode("/",$filename);
	$URL = "";
	for($i=0;$i<count($filename)-1;$i++) {
		$URL = $URL . $filename[$i] . "/";
		}
	$name[0] = $URL;
	$name[1] = $filename[count($filename)-1];

	return $name;
}
?>