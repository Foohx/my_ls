#!/usr/bin/php
<?php

require "posix.php";
require "reader.php";
require "print.php";

$opt_flags = array( 0, 0, 0, 0, 0);
$folder_list = array( 1);
$need_to_sort = array();
if (count( $argv) >= 2)
{
	for ($i=1; $i < count($argv); $i++) { 
		$opt = $argv[$i];
		if ($opt[0] === '-'){
			// now, get options
			for ($option=1; $option < strlen($opt); $option++) {
				if ($opt[$option] === 'a')
					$opt_flags[0] = 1;
				else if ($opt[$option] === 'l')
					$opt_flags[1] = 1;
				else if ($opt[$option] === 'R')
					$opt_flags[2] = 1;
				else if ($opt[$option] === 'r')
					$opt_flags[3] = 1;
			}
		} 
		else {
			$folder_list[$folder_list[0]] = $opt;
			$need_to_sort[$folder_list[0] -1] = $opt;
			$folder_list[0] += 1;
		}
	}
}
sort_files( $need_to_sort);
if ($opt_flags[3])
	$need_to_sort = array_reverse($need_to_sort);
for ($i=0; $i < count($need_to_sort); $i++) { 
	$folder_list[$i +1] = $need_to_sort[$i];
}

if ($folder_list[0] == 1){
	main_slave(".", $opt_flags);
} else {
	for ($i=1; $i < $folder_list[0]; $i++)
	{
		$path = $folder_list[$i];
		if ($folder_list[0] > 2)
			$opt_flags[4] = 1;
		main_slave($path, $opt_flags);

	}
}

function main_slave( $path, $opt_flags)
{
	$files = read_directory( $path, $opt_flags);
	if ($files != NULL && count($files))
	{
		if ($opt_flags[4] || $opt_flags[2])
			print("$path:\n");
		$details = ($opt_flags[1] ? print_manager($files, $opt_flags, 1) : NULL);
		if ($details == NULL)
			$details = print_manager($files, $opt_flags, 2);
		if ($opt_flags[1])
			print_manager($files, $opt_flags, 0, $details);
		else
			print_simple( $files, $opt_flags, $details);
		if ($opt_flags[4] || $opt_flags[2])
			print("\n");
		if ($opt_flags[2]) // -R
		{
			if ($opt_flags[3]) // -r
				$files = array_reverse( $files);
			for ($i=0; $i < count($details['map']); $i++) {
				$index 		= $details['map'][$i];
				$shorty 	= $files[$index]['fpath'];
				$shorty_fn 	= $files[$index]['filename'];
				if (is_dir($shorty) && !is_link($shorty) && $shorty_fn != "." && $shorty_fn != ".."){
					if (($shorty_fn[0] === '.' && $opt_flags[0]) || $shorty_fn[0] != '.')
						main_slave( $shorty, $opt_flags);
				} 
			}
		}
	}
	else {
		$stderr = fopen('php://stderr', 'w');
		fwrite($stderr, "myls: impossible d'accéder à $path: Aucun fichier ou dossier de ce type\n");
		fclose($stderr);
	}
}
