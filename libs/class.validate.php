<?php

/**
 *
 * Copyright (c) 2006,2007 Antti Harri <iku@openbsd.fi>
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

require_once('config.php');

// Original from: 
// http://fi2.php.net/manual/en/function.realpath.php#75992
function myRealPath($path) {

	// check if path begins with "/" ie. is absolute
	// if it isnt concat with script path
	if (strpos($path,"/") !== 0) {
		$base=dirname($_SERVER['SCRIPT_FILENAME']);
		$path=$base."/".$path;
	}

	// canonicalize
	$path=explode('/', $path);
	$newpath=array();
	for ($i=0; $i<sizeof($path); $i++) {
		if ($path[$i]==='' || $path[$i]==='.') continue;
		if ($path[$i]==='..') {
			array_pop($newpath);
			continue;
		}
		array_push($newpath, $path[$i]);
	}
	$finalpath="/".implode('/', $newpath);
	array_pop($newpath);
	$without_last="/".implode('/', $newpath);

	// check then return valid path or filename
	if (file_exists($finalpath) || file_exists($without_last)) {
		return ($finalpath);
	}
	else return FALSE;
}

class validate
{
	// Check if directory is under defined base directory.
	public static function source_file($rootpath, $dir)
	{
// 		global $_logger;
		if (empty($dir))
		// is this useless? || !preg_match("/^([a-z]|[A-Z]|[0-9]|_|-|:|\.|\/)*+$/", $dir2))
			return false;

		// Check that dir is contained in the base path
/*		$dir1 = myRealPath($this->base).'/';
		$dir2 = myRealPath($this->images.'/'.$dir2);*/
		$dir1 = myRealPath($rootpath).'/';
		$dir2 = myRealPath($dir).'/';

//printf ("$dir1<br>$dir2");
		if (strpos($dir2, $dir1) === 0)
			return true;

// 		$_logger->log('file outside BASE: '.myRealPath($dir2), 'error');
		return false;
	}

	public static function authenticate_dir($dir)
	{
		global $sacl;
		global $sacl_users;

		if (!is_array($sacl)||!is_array($sacl_users))
			return true;

//printf("<br>$dir<br>");
		$dir=myRealPath($dir).'/';
//printf("<br>$dir<br>");

		foreach ($sacl as $protected_album => $privileged_user) {
			$protected_album_rp=myRealPath($protected_album).'/';
// echo($dir);
// echo($protected_album);
// echo("<br/>\n");
			/*
			 * Album matches definitions, now we need to
			 * check is user has authenticated himself to us.
			 */
			if (strpos($dir, $protected_album_rp) === 0) {
// print($protected_album);
				if (!isset($_SESSION['authentications'][$protected_album]))
					return false;
				break;
			}
		}
// die();
		return true;
	}
	public static function login($dir, $user, $pass)
	{
		global $sacl;
		global $sacl_users;

		if (!is_array($sacl)||!is_array($sacl_users))
			return true;

		$dir=myRealPath($dir).'/';
		foreach ($sacl as $protected_album => $privileged_user) {
			$protected_album=myRealPath($protected_album).'/';
			if (strpos($dir, $protected_album) === 0) {
				if ($privileged_user==$user && $sacl_users[$privileged_user]==$pass)
					return true;
				break;
			}
		}
		return false;
	}
	public static function set_authorizations($user)
	{
		global $sacl;
		session_regenerate_id();
		if (!isset($_SESSION['authentications']))
			$_SESSION['authentications']=array();
		foreach ($sacl as $protected_album => $privileged_user) {
			if ($privileged_user==$user)
				$_SESSION['authentications'][$protected_album]=true;
		}
		return true;
	}
	public static function reset_authorizations()
	{
		/* Caution
		 * Do NOT unset the whole $_SESSION with unset($_SESSION)
		 * as this will disable the registering of session variables
		 * through the $_SESSION superglobal.
		 * http://fi2.php.net/manual/en/ref.session.php
		 */
		session_unset();
		session_regenerate_id();
	}
}

?>
