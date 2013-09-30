<?php

/**
 *
 * Copyright (c) 2007 Antti Harri <iku@openbsd.fi>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 *
 */

/*
 * TODO: Clean up the code.
 */

require_once('libs/class.validate.php');
require_once('config.php');

if (empty($_SERVER['QUERY_STRING']))
	die();

$filename=urldecode(basename($_SERVER['QUERY_STRING']));
$suffix=strtolower(pathinfo($filename, PATHINFO_EXTENSION));

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
	case "mp4":
	case "m4a":
	case "m4p":
	case "m4b":
	case "m4r":
	case "m4v":
		header("Content-type: video/mp4");
	break;
	case "ogg":
	case "ogv":
	case "oga":
	case "ogx":
	case "spx":
	case "opus":
		header("Content-type: video/ogg");
	break;
	case "webm":
		header("Content-type: video/webm");
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
