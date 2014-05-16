<?php

function __posix_getpwuid( $uid)
{
	$h = fopen("/etc/passwd", "r");
	$body = fread($h, filesize("/etc/passwd"));
	fclose($h);

	$matches = array();
	if (!preg_match_all ( "#([a-zA-Z0-9-_]+):.+:([0-9]+):([0-9]+)#", $body, $matches))
		return ("null");
	//var_dump($matches);
	for ($i=0; $i < count($matches[2]); $i++) { 
		if ($matches[2][$i] == $uid)
			return ($matches[1][$i]);
	}
	return ("null");
}

function __posix_getgrgid( $gid)
{
	$h = fopen("/etc/group", "r");
	$body = fread($h, filesize("/etc/group"));
	fclose($h);

	$matches = array();
	if (!preg_match_all ( "#([a-zA-Z0-9-_]+):.+:([0-9]+):#", $body, $matches))
		return ("null");
	for ($i=0; $i < count($matches[2]); $i++) { 
		if ($matches[2][$i] == $gid)
			return ($matches[1][$i]);
	}
	
	return ("null");
}