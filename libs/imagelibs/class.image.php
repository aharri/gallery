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

require_once('class.file.php');

/**
 * Image class
 *
 * Processes image files, generates thumbnails
 * and caches them.
 */
class image
{
	private $file=null;
	private $supported_types;
	private $thumbnail_format='jpeg';
	private $thumbnail_dir='./thumbnails';
	private $wmax=100;
	private $hmax=100;
	private $nozoom=true;

	/**
	 * When md5 checking is enabled the class can 
	 * determine changes to the source file
	 * and it can update the thumbnails.
	 */
	private $md5=true;

	private $cache=null;

	public function __construct()
	{
		$this->supported_types = array('jpeg', 'jpg', 'gif', 'png');
	}

	/**
	 * Sets MD5.
	 *
	 * Validates and sets MD5 of a file for later use.
	 */
	public function set_md5($status)
	{
		global $_logger;
		if (!is_bool($status)) {
			$_logger->log('could not set md5 flag, status not boolean', 'warning');
			return false;
		}
		$this->md5 = $status;
		return true;
	}

	/**
	 * Sets nozoom-option.
	 *
	 * Validates and sets nozoom-option.
	 *
	 * When no zoom is true the class won't scale
	 * images to higher resolution and thus it won't 
	 * pixelate them.
	 */
	public function set_nozoom($status)
	{
		global $_logger;
		if (!is_bool($status)) {
			$_logger->log('could not set no zoom flag, status not boolean', 'warning');
			return false;
		}
		$this->nozoom = $status;
		return true;
	}

	/**
	 * Sets thumbnail maximums.
	 *
	 * Validates and sets thumbnail maximum dimensions.
	 */	
	public function set_thumbnail_maximums($width, $height)
	{
		if (!is_numeric($width) || !is_numeric($height) || $width<1 || $height<1)
			return false;
		$this->wmax = $width;
		$this->hmax = $height;
		return true;
	}

	/**
	 * Sets thumbnail directory.
	 *
	 * Validates and sets thumbnail cache directory.
	 */
	public function set_thumbnail_dir($dir)
	{
		if (!is_dir($dir) || !is_writable($dir))
			return false;
		$this->thumbnail_dir = $dir;
		return true;
	}

	/**
	 * Sets thumbnail format.
	 *
	 * Validates and sets thumbnail format.
	 *
	 * TODO: Better (more universal) format handling.
	 */
	public function set_thumbnail_format($format)
	{
		$format = strtolower($format);
		switch ($format)
		{
			case "jpeg":
			case "jpg":
			case "png":
			case "gif":
				$this->thumbnail_format = $format;
				return true;
			default:
				return false;
		}
	}

	/**
	 * Gets thumbnail format
	 */
	public function get_thumbnail_format() { return $this->thumbnail_format; }

	/**
	 * Sets file.
	 *
	 * Validates and sets file to be processed.
	 */
	public function set_file($file)
	{
		if (empty($file) || !is_file($file) || !is_readable($file)) {
			$this->file=null;
			return false;
		}

		$suffix = pathinfo($file, PATHINFO_EXTENSION);
		if (in_array($suffix, $this->supported_types)) {
			$this->file = $file;
			return true;
		}
		$this->file=null;
		return false;
	}

	/**
	 * Gets maximums.
	 *
	 * Validates and gets maximum dimensions of a file.
	 */
	public function get_max()
	{
		global $_logger;

		// No file set.
		if (!$this->file) {
			$_logger->log('Could not get maximum dimensions, filename empty', 'warning');
			return false;
		}

		$sizes = getimagesize($this->file);
	
		if ($sizes) {
			list($width, $height, $type, $attr) = $sizes;
		} else {
			return array(0, 0, 0, 0);
		}

		if ($width == 0 || $height == 0) 
			return array(0, 0, 0, 0);

		// We don't want to zoom to a grainier picture.
		if ($this->nozoom) 
		{
			if ($this->wmax > $width && $this->hmax > $height) 
				return array($width, $height, $width, $height);
		}

		// "Landscape" image.
		if ($width > $height)
		{
			$ratio = $height / $width;
			$neww = $this->wmax;
			$newh = intval($ratio * $neww);
			// New height must not be bigger than maximum height.
			if ($newh > $this->hmax) {
				$ratio = $width / $height;
				$newh = $this->hmax;
				$neww = intval($ratio * $newh);
			}
		// "Portrait" image.
		} else {
			$ratio = $width / $height;
			$newh = $this->hmax;
			$neww = intval($ratio * $newh);
			// New width must not be bigger than maximum width.
			if ($neww > $this->wmax) {
				$ratio = $height / $width;
				$neww = $this->wmax;
				$newh = intval($ratio * $neww);
			}
		}
		return array($neww, $newh, $width, $height);
	}

	/**
	 * Gets MD5.
	 *
	 * Validates and gets MD5 sum of a file from cache.
	 *
	 * Used only internally.
	 */
	private function get_md5($file)
	{
		global $_logger;

		$md5sums=$this->thumbnail_dir.'/.md5sums';

		// Hmpf, cache isn't loaded.. We have to load it from file.
		if (!$this->cache)
		{
			$this->cache=array();
 			$temp=file::read($md5sums);
			if ($temp===false) {
				$_logger->log("Failed to read in get_md5!", 'warning');
				return false;
			}
			$temp=explode("\n", $temp);
			foreach($temp as $t)
			{
				$t=explode('=', $t);
				if (count($t)==2)
					$this->cache[$t[0]]=$t[1];
			}
		}

		if (isset($this->cache[$file])) {
			return $this->cache[$file];
		}

		return false;
	}

	/**
	 * MD5 Cache flusher
	 *
	 * Attempts to flush all cache into a file.
	 *
	 * FIXME: Race condition when writing. This should
	 * fixed in file class.
	 */
	public function flush_cache()
	{
		global $_logger;

		// Cache disabled, do not flush.
		if (!$this->md5) {
			return false;
		}

		if ($this->cache && is_array($this->cache))
		{
			$md5sums=$this->thumbnail_dir.'/.md5sums';
			$lines=array();
			foreach ($this->cache as $k => $v)
			{
				$lines[]="{$k}={$v}";
			}
			$lines=implode("\n", $lines);
			$temp=file::write($md5sums, 'w', $lines, 0600);
			if ($temp!==false) {
				return true;
			}
			$_logger->log("Image md5sums cache flush failed!", 'warning');
		}
		return false;
	}

	/**
	 * Compares MD5.
	 *
	 * Compares MD5 of a file against what is in index.
	 * 
	 * Used only internally.
	 */
	private function compare_md5($file_to_cmp)
	{
		global $_logger;

		// Caching disabled -> disable queries.
		if (!$this->md5)
		{
			return false;
		}
		if (($data=$this->get_md5($file_to_cmp))===false)
		{
			$_logger->log('Entry not in cache file: '.$file_to_cmp, 'debug');
			return false;
		}
		if ($data==md5_file($file_to_cmp))
		{
			return $file_to_cmp;
		}
		return false;
	}

	/**
	 * Adds file to be stored in index.
	 *
	 * The actual index file is not written until
	 * flush-method is called.
	 *
	 * Used only internally.
	 */
	private function add_cached($file)
	{
		if (!$this->md5) {
			return false;
		}
		$md5_sum = md5_file($file);
		if (!$this->cache || !is_array($this->cache)) {
			$this->cache=array();
		}
		$this->cache[$file]=$md5_sum;
		return true;
	}

	/**
	 * Generates thumbnails.
	 *
	 * Generates thumbnails of files and returns
	 * the filename created. Previously cached
	 * entries will be used if they exist to 
	 * speed things up. On error it will either
	 * return false if no file has been set or
	 * the original filename.
	 */
	function generate_thumb()
	{
		global $_logger;

		// No image set!
		if (!$this->file) {
			$_logger->log('No file set', 'warning');
			return false;
		}
		$filename = preg_replace('#^.*/#', '', $this->file);
		// Hardcoded.. but so what? 90% is enough for everyone. ;D
		$jpg_quality = '90';
	
		// Check if cached file already exists.
		$cache_file = "{$this->thumbnail_dir}/{$filename}.{$this->thumbnail_format}";

		// Return cache_file on these conditions:
		//
		// If md5 sum checking tells the file is good.
		// OR
		// If md5 sum checking is disabled and cache image is readable.
		if (($this->compare_md5($this->file)!==false && is_readable($cache_file)) || (!$this->md5&&is_readable($cache_file)))
		{
			return $cache_file;
		}

		$_logger->log('File not in cache, re-generating: '.$this->file, 'debug');

		// Get specs from original image.
		list($width, $height, $width_orig, $height_orig) = $this->get_max();

		// Create new image.
		$cache_img = imagecreatetruecolor($width, $height);
		$suffix = pathinfo($this->file, PATHINFO_EXTENSION);
		switch ($suffix)
		{
			case "jpg":
			case "jpeg":
				$orig_img = imagecreatefromjpeg($this->file);
			break;
			case "png":
				$orig_img = imagecreatefrompng($this->file);
			break;
			case "gif";
				$orig_img = imagecreatefromgif($this->file);
			break;
		}

		if (!$orig_img) {
			$_logger->log('Could not generate thumbnail, load failed!', 'warning');
			return $this->file;
		}

		if (!imagecopyresampled($cache_img, $orig_img, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig)) {
			$_logger->log('Could not generate thumbnail, imagecopy failed!', 'warning');
			return $this->file;
		}
	
		switch ($this->thumbnail_format)
		{
			case "jpg":
			case "jpeg":
				$val = imagejpeg($cache_img, $cache_file, $jpg_quality);
				break;
			case "png":
				$val = imagepng($cache_img, $cache_file);
				break;
			default:
				$_logger->log('Could not generate thumbnail, unknown thumbnail format!', 'warning');
				return $this->file;
				break;
		}
	
		if (!$val) {
			$_logger->log('Could not generate thumbnail, image function failed!', 'warning');
			return $this->file;
		}
		// Clean up memory bitmaps.
		imagedestroy($orig_img);
		imagedestroy($cache_img);

		$this->add_cached($this->file);
		$this->flush_cache();

		return $cache_file;
	}

	/**
	 * Removes directories and files.
	 *
	 * Removes directories and files in a similar
	 * way like good old 'rm -rf' in UNIX.
	 *
	 * TODO: Moving this to a more approriate class
	 * should be considered
	 */
	private function rmrf($file)
	{
		global $_logger;

		if (is_file($file)) {
			$_logger->log('Removing file '.$file, 'info');
			return unlink($file);
		}
		if (is_dir($file)) {
			if (!$dh = @opendir($file))
				return false;
			while (false !== ($obj = readdir($dh))) {
				if ($obj=='.' || $obj=='..')
					continue;
				if (is_file($file.'/'.$obj)) {
					$_logger->log('Removing file '.$file.'/'.$obj, 'info');
					unlink($file.'/'.$obj);
				}
				if (is_dir($file.'/'.$obj))
					$this->rmrf($file.'/'.$obj);
			}
			closedir($dh);
			$_logger->log('Removing directory '.$file, 'info');
			return rmdir($file);
		}
		return false;
	}

	/**
	 * Compares directory against cache.
	 *
	 * Takes directory as input and compares it against
	 * directory set earlier with set_thumbnail_dir().
	 */
	public function compare_cache($comparison_dir)
	{
		global $_logger;

		if (!$this->thumbnail_dir) {
			$_logger->log('Thumbnail directory not set, no comparison possible!', 'error');
			return false;
		}

		if (($handle = opendir($this->thumbnail_dir)) === false) {
			$_logger->log('Failed to open directory! '.$this->thumbnail_dir, 'error');
			return false;
		}

		// Build up a list of files to be removed
		// and remove them recursively with special method.
		$deleted=array();
		while ($cache_file = readdir($handle)) {

			// Ignore dot-files.
			if (strpos($cache_file, '.')===0) 
				continue;

			$full_cache_file=$this->thumbnail_dir.'/'.$cache_file;

			// Strip last suffix if it's file.
			$orig_file = $cache_file;
			if (is_file($full_cache_file)) {
				$orig_file = substr($cache_file, 0, strrpos($cache_file, '.'));
			}

			$full_orig_file=$comparison_dir.'/'.$orig_file;
			if (!is_file($full_orig_file) && !is_dir($full_orig_file)) {
				if (is_file($full_cache_file))
					$deleted[] = $full_orig_file;
				$this->rmrf($full_cache_file);
			}

		}

		// Clean the .md5sums index file.
		if (!empty($deleted)) {
			if (defined('DEVEL')) {
				foreach ($deleted as $cur_deleted)
					$_logger->log('Removing from index: '.$cur_deleted, 'info');
			}
			$input=file::read($this->thumbnail_dir.'/.md5sums');
			if ($input===false) {
				$_logger->log("Failed to read input in compare_cache!", 'warning');
				return false;
			}
			$new_data=array();
			$input=explode("\n", $input);
			foreach ($input as $line) {
				$elements=explode('=', $line);
				if (array_search($elements[0], $deleted)===false)
					$new_data[]=$line;
			}
			$new_data=implode("\n", $new_data);
			$temp=file::write($this->thumbnail_dir.'/.md5sums', 'w', $new_data, 0600);
			if ($temp===false) {
				$_logger->log("Failed to write output in compare_cache!", 'warning');
				return false;
			}
		}
	}
}

?>
