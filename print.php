<?php

define("COLOR_BLACK",		  0);
define("COLOR_RED",	 		  1);
define("COLOR_GREEN",		  2);
define("COLOR_YELLOW",		  3);
define("COLOR_BLUE",		  4);
define("COLOR_MAGENTA", 	  5);
define("COLOR_CYAN", 		  6);
define("COLOR_WHITE",		  7);

define("COLOR_ON_TEXT",		 30);	// simple color
define("COLOR_ON_TEXT_LIT",	 90);	// light color
define("COLOR_ON_BKGND",	 40);	// simple color background
define("COLOR_ON_BKGND_LIT",100);	// light colo background

define("RDASH_DIR",		COLOR_BLUE+COLOR_ON_TEXT_LIT);	// color for directory
define("RDASH_LINK_OK",	COLOR_CYAN+COLOR_ON_TEXT_LIT);	// color for valid link
define("RDASH_LINK_KO",	COLOR_RED+COLOR_ON_TEXT_LIT);	// color for invalid link


function print_in_color( $text, $color)
{
	return ("\033[".$color."m".$text."\e[0m");
}
function print_manager( $files, $flags, $mode = 0, $details = NULL)
{
	if ($flags[3]) // -r
		$files = array_reverse( $files);
	if ($mode){
		$give_some_data = array(
			'total_size' 	=> 0,
			'index_set'		=> false,
			'index_start'	=> 0,
			'index_stop'	=> 0,
			'map'			=> array(),
			'i'				=> 0,
			'filename'		=> 0,
			'nlink'			=> 0,
			'usrname'		=> 0,
			'grpname'		=> 0,
			'size'			=> 0,
			'm_month'		=> 0,
			'm_day'			=> 0,
			"m_time"		=> 0
		);
	}
	if ($flags[1] && $details != NULL)
		print("total " . $details['total_size'] . "\n");
	for ($i=0; $i < count($files); $i++) {
		$f = $files[$i];
		if (($f['filename'][0] === '.' && $flags[0]) || $f['filename'][0] !== '.') // -a
		{
			if (!$mode && $flags[1] && $details != NULL) // -l
				print_list( $f, $flags, $details);
			else if (!$mode)
				print_simple( $f, $flags, $i, $details);
			else if ($mode) {
				if ($flags[1])
					$give_some_data['total_size'] += $f['blocks'];
				if (!$give_some_data['index_set']){
					$give_some_data['index_start'] 	= $i;
					$give_some_data['index_set'] 	= true;
				}
				$give_some_data['index_stop'] = $i;
				$give_some_data['map'][count($give_some_data['map'])] = $i;
				foreach ($give_some_data as $k => $v) {
					if (isset($f[$k])){
						$tmp_str =  $f[$k];
						if (!is_string($tmp_str))
							$tmp_str = (string) $tmp_str;
						$give_some_data[$k] = (strlen($tmp_str) > $v ? strlen($tmp_str) : $v);
					}
				}
			}
		}
	}	
	if ($mode){
		$give_some_data['total_size'] = ($give_some_data['total_size']?$give_some_data['total_size']/2:0);
		return $give_some_data;
	}
}
function print_simple( $f, $flags, $details)
{
	if ($flags[3]) // -r
		$f = array_reverse( $f);
	$cols_width  = $details['filename'];
	$cols_number = 0;
	$cols_in_term = intval(`tput cols`);
	$rows_number = 0;
	$items_number = count($details['map']);
	if ($items_number == 0)
		return NULL;
	$cols_number = (int)($cols_in_term / ($cols_width));
	if ($items_number > $cols_number){
		$rows_number = (int)($items_number / $cols_number);
		if (($rows_number * $cols_number) < $items_number)
			$rows_number += 1;
	}
	else
		$rows_number = 1;
	$cols_width = array();
	for ($i_row=0; $i_row < $rows_number; $i_row++) { 
		for($i_col=0; $i_col < $cols_number; $i_col++){
			$index = $i_row + ($i_col * $rows_number);
			if (isset($details['map'][$index])){
				if (!isset($cols_width[$i_col]) || strlen($f[$details['map'][$index]]['filename']) > $cols_width[$i_col])
					$cols_width[$i_col] = strlen($f[$details['map'][$index]]['filename']);
			}
		}
	}
	$spacer = function($max,$str,$start=true){
		if (strlen($str) > $max){ return $str; }
		for ($i=strlen($str); $i < $max; $i++) { 
			if ($start)
				$str = " ".$str;
			else
				$str .= " ";
		}
		return $str;
	};
	for ($i_row=0; $i_row < $rows_number; $i_row++) { 
		for($i_col=0; $i_col < $cols_number; $i_col++){
			$index = $i_row + ($i_col * $rows_number);
			if (isset($details['map'][$index])){
				//print($index . "  ");
				$mega_filename = $f[$details['map'][$index]]['fpath'];
				if (is_dir($mega_filename) && !is_link($mega_filename))	// directory
					$mega_filename = print_in_color(
						$spacer( $cols_width[$i_col], $f[$details['map'][$index]]['filename'], false),
						RDASH_DIR
					);
				else if (is_link($mega_filename)){	// link
					if (file_exists($mega_filename))
						$mega_filename = print_in_color(
							$spacer( $cols_width[$i_col], $f[$details['map'][$index]]['filename'], false),
							RDASH_LINK_OK
						);
					else
						$mega_filename = print_in_color(
							$spacer( $cols_width[$i_col], $f[$details['map'][$index]]['filename'], false),
							RDASH_LINK_KO
						);
				}
				else
					$mega_filename = $spacer( $cols_width[$i_col], $f[$details['map'][$index]]['filename'], false);
				print($mega_filename);
				print( "  ");
			}
		}
		print("\n");
	}
}
function print_list( $f, $flags, $details)
{
	$spacer = function($max,$str,$start=true){
		if (strlen($str) > $max){ return $str; }
		for ($i=strlen($str); $i < $max; $i++) { 
			if ($start)
				$str = " ".$str;
			else
				$str .= " ";
		}
		return $str;
	};
	print($f['perms']);
	print(" ");
	print( $spacer( $details['nlink'], (string)$f['nlink'], true) );
	print(" ");
	print( $spacer( $details['usrname'], $f['usrname'], false) );
	print(" ");
	print( $spacer( $details['grpname'], $f['grpname'], false) );
	print(" ");
	print(  $spacer( $details['size'], (string)$f['size'], true) );
	print(" ");
	print(  $spacer( $details['m_month'], $f['m_month'], false) );
	print(" ");
	print(  $spacer( $details['m_day'], (string)$f['m_day'], true) );
	print(" ");
	print(  $spacer( $details['m_time'], (string)$f['m_time'], true) );
	print(" ");
	$mega_filename = $f['fpath'];
	if (is_dir($mega_filename) && !is_link($mega_filename))
		$mega_filename = print_in_color( $f['filename'], RDASH_DIR);
	else if (is_link($mega_filename)){
		if (file_exists($mega_filename)){
			$mega_filename = print_in_color( $f['filename'], RDASH_LINK_OK);
			$mega_filename .= " -> " . $f['target'];
		} else {
			$mega_filename = print_in_color( $f['filename'], RDASH_LINK_KO);
			$mega_filename .= " -> ";
			$mega_filename .= print_in_color( $f['target'], RDASH_LINK_KO);
		}
	}
	else
		$mega_filename = $f['filename'];
	print($mega_filename);
	print("\n");
}
