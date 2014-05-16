<?php

function read_directory( $path, $flags)
{
	if (!($b = file_exists($path)))
		return NULL;
	$files = array();
	if ($handle = opendir( $path)) {
	    while (false !== ($entry = readdir($handle))) {
	    	$files[count($files)] = $entry;
	    }
	    closedir($handle);
	    sort_files( $files);
		$files = read_stats( $files, $path);
	    return (count($files) ? $files : NULL);
	}
	return NULL;
}
function sort_files_cmp( $a, $b)
{
	return strcmp($a["new"], $b["new"]);	
}
function sort_files( &$table)
{
	$new = array();
	for ($i=0; $i < count($table); $i++) { 
		$tmp = $table[$i];
		$tmp_b = "";
		if (strlen($tmp) >= 2 && $tmp[0] === '.' && $tmp[1] != '.')
		{
			for ($j=1; $j < strlen($tmp); $j++) { 
				$tmp_b .= $tmp[$j];
			}
		}
		$new[$i] = array(
			'real' => $table[$i],
			'new' => strtolower((strlen($tmp_b) ? $tmp_b : $table[$i]))
		);
	}
	usort($new, "sort_files_cmp");
	$table = array();
	for ($i=0; $i < count($new); $i++) { 
		$table[$i] = $new[$i]["real"];
	}	
}
function read_permissions( $perms)
{
	if (($perms & 0xC000) == 0xC000)
	    $info = 's';
	elseif (($perms & 0xA000) == 0xA000)
	    $info = 'l';
	elseif (($perms & 0x8000) == 0x8000)
	    $info = '-';
	elseif (($perms & 0x6000) == 0x6000)
	    $info = 'b';
	elseif (($perms & 0x4000) == 0x4000)
	    $info = 'd';
	elseif (($perms & 0x2000) == 0x2000)
	    $info = 'c';
	elseif (($perms & 0x1000) == 0x1000)
	    $info = 'p';
	else
	    $info = 'u';
	$info .= (($perms & 0x0100) ? 'r' : '-');
	$info .= (($perms & 0x0080) ? 'w' : '-');
	$info .= (($perms & 0x0040) ?
	            (($perms & 0x0800) ? 's' : 'x' ) :
	            (($perms & 0x0800) ? 'S' : '-'));
	$info .= (($perms & 0x0020) ? 'r' : '-');
	$info .= (($perms & 0x0010) ? 'w' : '-');
	$info .= (($perms & 0x0008) ?
	            (($perms & 0x0400) ? 's' : 'x' ) :
	            (($perms & 0x0400) ? 'S' : '-'));
	$info .= (($perms & 0x0004) ? 'r' : '-');
	$info .= (($perms & 0x0002) ? 'w' : '-');
	$info .= (($perms & 0x0001) ?
	            (($perms & 0x0200) ? 't' : 'x' ) :
	            (($perms & 0x0200) ? 'T' : '-'));
	return $info;
}

function read_stats( $files, $path)
{
	$files_data = array();
	for ($i=0; $i < count($files); $i++) {
		$ffilename = $path . ($path[strlen($path)-1] === '/' ? '' : '/') . $files[$i];
		$r_stat = (!is_link($ffilename) ? stat($ffilename) : lstat($ffilename));
		$grp_name = __posix_getgrgid( $r_stat["gid"]);	//posix_getgrgid( $r_stat["gid"]);
		$usr_name = __posix_getpwuid ( $r_stat["uid"]);	//posix_getpwuid ( $r_stat["uid"]);
		$mod_month = function ($timestamp){
			$m_list = array("janv.","fevr.","mars","avril","mai","juin","juil.","aout","sept.","oct.","nov.","dec.");
			$m = date( 'n', $timestamp);
			return ($m_list[$m -1] . "\0");
		};
		$files_data[$i] = array(
			'path'		=> $path,
			'filename' 	=> $files[$i],
			'fpath'		=> $ffilename,
			'target'	=> (is_link($ffilename) ? readlink($ffilename) : false),
			'grpname'	=> $grp_name,	// $grp_name["name"],
			'usrname'	=> $usr_name,	// $usr_name["name"],
			'perms'		=> read_permissions( $r_stat["mode"]),
			'nlink'		=> $r_stat["nlink"],
			'size'		=> $r_stat["size"],
			'mtime'		=> $r_stat["mtime"],
			'm_month'	=> $mod_month( $r_stat["mtime"]),
			'm_day'		=> date( 'j', $r_stat["mtime"]),
			"m_time"	=> (date('Y',$r_stat["mtime"])==date('Y',time())?	// if file year equ. current, show time else show year
											date('H:i',$r_stat["mtime"]):
											date('Y',$r_stat["mtime"]) ),
			'blocks'	=> $r_stat["blocks"],
			'blksize'	=> $r_stat["blksize"]
		);
	}
	return $files_data;
}