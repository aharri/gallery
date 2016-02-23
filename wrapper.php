<?php

/**
 *
 * Copyright (c) 2007,2013 Antti Harri <iku@openbsd.fi>
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

/* Adapted from Thomas Thomassen's code:
 * http://mobiforge.com/developing/story/content-delivery-mobile-devices#byte-ranges
 */
function rangeDownload($file) {
 
	$fp = @fopen($file, 'rb');
 
	$size   = filesize($file); // File size
	$length = $size;           // Content length
	$start  = 0;               // Start byte
	$end    = $size - 1;       // End byte
	// Now that we've gotten so far without errors we send the accept range header
	/* At the moment we only support single ranges.
	 * Multiple ranges requires some more work to ensure it works correctly
	 * and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
	 *
	 * Multirange support annouces itself with:
	 * header('Accept-Ranges: bytes');
	 *
	 * Multirange content must be sent with multipart/byteranges mediatype,
	 * (mediatype = mimetype)
	 * as well as a boundry header to indicate the various chunks of data.
	 */
	header("Accept-Ranges: 0-$length");
	// header('Accept-Ranges: bytes');
	// multipart/byteranges
	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
	if (isset($_SERVER['HTTP_RANGE'])) {
 
		$c_start = $start;
		$c_end   = $end;
		// Extract the range string
		list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
		// Make sure the client hasn't sent us a multibyte range
		if (strpos($range, ',') !== false) {
 
			// (?) Shoud this be issued here, or should the first
			// range be used? Or should the header be ignored and
			// we output the whole content?
			header('HTTP/1.1 416 Requested Range Not Satisfiable');
			header("Content-Range: bytes $start-$end/$size");
			// (?) Echo some info to the client?
			exit;
		}
		// If the range starts with an '-' we start from the beginning
		// If not, we forward the file pointer
		// And make sure to get the end byte if spesified
		if ($range == '-') {
 
			// The n-number of the last bytes is requested
			$c_start = $size - substr($range, 1);
		}
		else {
 
			$range  = explode('-', $range);
			$c_start = $range[0];
			$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
		}
		/* Check the range and make sure it's treated according to the specs.
		 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
		 */
		// End bytes can not be larger than $end.
		$c_end = ($c_end > $end) ? $end : $c_end;
		// Validate the requested range and return an error if it's not correct.
		if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
 
			header('HTTP/1.1 416 Requested Range Not Satisfiable');
			header("Content-Range: bytes $start-$end/$size");
			// (?) Echo some info to the client?
			exit;
		}
		$start  = $c_start;
		$end    = $c_end;
		$length = $end - $start + 1; // Calculate new content length
		fseek($fp, $start);
		header('HTTP/1.1 206 Partial Content');
	}
	// Notify the client the byte range we'll be outputting
	header("Content-Range: bytes $start-$end/$size");
	header("Content-Length: $length");
 
	// Start buffered download
	$buffer = 1024 * 8;
	while(!feof($fp) && ($p = ftell($fp)) <= $end) {
 
		if ($p + $buffer > $end) {
 
			// In case we're only outputtin a chunk, make sure we don't
			// read past the length
			$buffer = $end - $p + 1;
		}
		set_time_limit(0); // Reset time limit for big files
		echo fread($fp, $buffer);
		flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
	}
 
	fclose($fp);
 
}

if (empty($_SERVER['QUERY_STRING']))
	die();

$filename=urldecode(basename($_SERVER['QUERY_STRING']));
$suffix=strtolower(pathinfo($filename, PATHINFO_EXTENSION));

$dirname=dirname($_SERVER['QUERY_STRING']);
$mode=$dirname;
if (strpos($dirname, '/') !== FALSE)
	$mode=substr($dirname, 0, strpos($dirname, '/'));
$dirname=urldecode(substr($dirname, strlen($mode)+1));

switch ($mode)
{
	case "small":	$from = IMGDST2; break;
	case "big": 	$from = IMGDST1; break;
	case "video":	$from = IMGSRC; break;
	default: die('Invalid mode');
}

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
	default: die();
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

	if (isset($_SERVER['HTTP_RANGE']) && $mode == "video")  { // do it for any device that supports byte-ranges not only iPhone
 
		rangeDownload("$from/$dirname/$filename");
	}
	else {

header("Content-Length: ".@filesize("$from/$dirname/$filename"));
@readfile("$from/$dirname/$filename");
}
// > From ACMS 1.0.9.2

?>
