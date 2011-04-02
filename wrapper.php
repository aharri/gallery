<?php

/*
 * $Id: wrapper.php,v 1.7 2007/09/29 18:28:33 iku Exp $
 *
 * Copyright (c) 2007 Antti Harri <iku@openbsd.fi>
 * 
 * TODO: Clean up the code.
 * FIXME: Suffix handling.
 */

require_once('libs/class.validate.php');
require_once('config.php');

if (empty($_SERVER['QUERY_STRING']))
	die();

$filename=urldecode(basename($_SERVER['QUERY_STRING']));
$suffix=strtolower(substr($filename, strrpos($filename, '.')+1));

$dirname=dirname($_SERVER['QUERY_STRING']);
$mode=$dirname;
if (strpos($dirname, '/') !== FALSE)
	$mode=substr($dirname, 0, strpos($dirname, '/'));
$dirname=urldecode(substr($dirname, strlen($mode)+1));

if($mode!='big'&&$mode!='small')
	die('Invalid mode');
$from=($mode=='big')?IMGDST1:IMGDST2;

session_start();

if (!validate::authenticate_dir($dirname)||!validate::source_file($from, "$from/$dirname/$filename"))
	die();

if (!is_file("$from/$dirname/$filename"))
	die('No such file');

switch ($suffix)
{
	case "jpg":
	case "jpeg":
		header("Content-type: image/jpeg");
	break;
	case "png":
		header("Content-type: image/png");
	break;
	case "gif":
		header("Content-type: image/gif");
	break;
	default:
		die();
}

// This part has been adapted from AngelineCMS
// version 1.0.9.2 <
header("Pragma: public");
header("Cache-control: public");
$lm=filemtime("$from/$dirname/$filename");

if(!defined('DEVEL')) {
	if(array_key_exists('HTTP_IF_MODIFIED_SINCE',$_SERVER)) {
		$ifModified=strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
		if($ifModified<=$lm) {
			header("{$_SERVER['SERVER_PROTOCOL']} 304 Not Modified");
			die();
		}
	}
}
header("Expires: ".date("r",time()+3600*24));
header("Last-Modified: ".date("r",$lm));

header("Content-Length: ".@filesize("$from/$dirname/$filename"));
@readfile("$from/$dirname/$filename");
// > From ACMS 1.0.9.2

?>
