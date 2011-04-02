<?php

/**
 * $Id: class.file.php,v 1.3 2007/10/01 00:35:46 iku Exp $
 *
 * Copyright (c) 2006,2007
 * Antti Harri / OpenHosting Harri <iku@openbsd.fi>
 *
 */

if (!class_exists('file'))
{
	class file 
	{
		public function __construct()
		{
		}
		/**
		 * Validates input and reads data.
		 */
		public static function read($file)
		{
			if (!is_file($file) || !is_readable($file)) {
				return false;
			}
			// Short-hand for empty file.
			$fsize = filesize($file);
			if ($fsize == 0) return '';

			$handle = fopen($file, "r");
			$contents = fread($handle, $fsize);
			fclose($handle);
			return $contents;
		}
		/**
		 * Validates input and writes to the file.
		 *
		 * mode w: overwrite file.
		 * mode a: append at the end of file.
		 */
		public static function write($file, $mode='w', $data, $filemode=null)
		{
			global $_logger;
			// New file? Then we need +w on the dir to create it.
			if (!is_file($file) && !is_writable(dirname($file))) {
				$_logger->log('Cannot create file because directory not writable: '.dirname($file), 'error');
				return false;
			// File exists, but can we write to it?
			} else if (is_file($file) && !is_writable($file)) {
				$_logger->log('File not writable: '.$file, 'error');
				return false;
			}

			// Open file
			if (!($handle = fopen($file, $mode)))
				return false;
			$ret=fwrite($handle, $data);
			fclose($handle);
			if ($filemode)
				chmod($file, $filemode);
			return $ret;
		}
	}
}

?>
