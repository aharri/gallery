<?php

/**
 * Copyright (c) 2007,2011,2013 Antti Harri <iku@openbsd.fi>
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

/**
 * TODO:
 *
 *  Maybe logout?
 *
 *  Clean URLs a little by using '-' instead of spaces (%20)
 *  and by hiding the extension of the file.
 *
 *  Check out the layout arranges thumbnails incorrectly.
 *
 *  For video support:
 *   - make a thumbnail of the video for "browse" page
 *   - use wrapper for videos
 */
require_once('libs/class.validate.php');
require_once('libs/class.acms_xml.php');
require_once('libs/imagelibs/class.image.php');

define ('VERSION', '1.5-current');
define ('AUTHOR', 'Antti Harri <iku@openbsd.fi>');

class pager
{
	private $page_count;
	private $page_num;
	public function __construct()
	{
		session_start();
	}

	public function init($row_count, $rows_per_page, &$page_num=0)
	{
		$page_count=0;
		$page_count = ceil($row_count / $rows_per_page);

		if ($page_num>$page_count)
			$page_num=$page_count;
		if($page_num<=0)
			$page_num=1;
		$this->page_count = $page_count;
		$this->page_num = $page_num;
	}

	private function gen_url($page_num)
	{
		$result = "";
		$url=$_SERVER['QUERY_STRING'];

		// Check if URL ends with '/digits'
		if (preg_match('#/\d+$#', $url))
		{
			// It does! Change the number accordingly
			$regexp="#/\d+$#";
			$result=preg_replace($regexp, '/'.$page_num, $url);
		}
		else
		{
			// It didn't so append it
			$result="$url/$page_num";
		}

		return $result;
	}

	public function genlinks()
	{
		if($this->page_count > 1)
		{
			$paging=array();
			$max_dlinks = 15;
			$hmax_dlinks = ceil($max_dlinks/2);
			$st = array('c_prev' => '', 'c_direct_links' => '', 'c_next' => '');
			$start = $this->page_num-$hmax_dlinks;
			if($start < 0) $start = 0;
			$end = $start+$max_dlinks;
			if($end > $this->page_count)
			{
				$start -= $end-$this->page_count;
				if($start < 0) $start = 0;
				$end = $this->page_count;
			}
			$paging['previous']->lid = 'link_previous';
			$paging['previous']->name = 'previous';
			if($this->page_num-1 > 0)
			{
				$num = $this->page_num-1;
				$paging['previous']->link = $this->gen_url($num);
			}
			for($i = $start+1; $i < $end+1; $i++)
			{
				$paging[$i]->lid = 'link_'.$i;
				$paging[$i]->name = $i;
				if($this->page_num != $i)
				{
					$paging[$i]->link = $this->gen_url($i);
				}
			}
			$paging['next']->lid = 'link_next';
			$paging['next']->name = 'next';
			if($this->page_num < $this->page_count)
			{
				$num = $this->page_num+1;
				$paging['next']->link = $this->gen_url($num);
			}
			return $paging;
		}
		return array();
	}
}

class logger_lite
{
	private $stuff='';
	public function log($msg, $level='info')
	{
		$this->stuff.="$level\t$msg\n";
	}
	public function flush()
	{
		return $this->stuff;
	}
	public function __construct()
	{
	}
}

class gallery 
{
	private $xmlvars;
	private $debug;
	private $hiliting;
	private $content = '';
	private $error;
	private $layout;

	private $base;
	private $images;
	private $gallery_big;
	private $gallery_small;

	private $dim_big;
	private $dim_small;

	private $items;

	private $page=1;
	public function __construct()
	{
		$this->xmlroot=new XML_XML;
	}

	private function load_configuration()
	{
		global $_logger;
		// Get the local configuration (if available)
		@include ('./config.php');

		// Set the values.
		$this->debug = defined('DEBUG')?DEBUG:false;

		$this->hiliting = defined('HILITING')?HILITING:30;

		$this->base = defined('BASE')?BASE:'..';
		$this->images = defined('IMGSRC')?IMGSRC:$this->base.'/images';
		$this->gallery_big = defined('IMGDST1')?IMGDST1:'images/big';
		$this->gallery_small = defined('IMGDST2')?IMGDST2:'images/small';
		$this->layout = defined('TEMPLATE')?TEMPLATE:'internal';

		// these are maximums, proportions are always kept!
		if (defined('DIMS1')) {
			$temp=explode('x', DIMS1);
			$this->dim_big=array('width'=>$temp[0], 'height'=>$temp[1]);
		} else {
			$this->dim_big=array('width'=>'750', 'height'=>'550');
		}
		if (defined('DIMS2')) {
			$temp=explode('x', DIMS2);
			$this->dim_small=array('width'=>$temp[0], 'height'=>$temp[1]);
		} else {
			$this->dim_small=array('width'=>'200', 'height'=>'150');
		}

		$this->items = defined('ITEMS_PER_PAGE')?ITEMS_PER_PAGE:15;

		// Validate the values.
		if (!is_dir($this->base)) {
			$_logger->log('base directory not readable by user: '.myRealPath($this->base), 'error');
			return false;
		}
		if (!is_dir($this->images) || !is_readable($this->images)) {
			$_logger->log('images directory not readable by user', 'error');
			return false;
		}
		if (!is_dir($this->gallery_big)) {
			$_logger->log('attempting to create gallery_big: '.myRealPath($this->gallery_small), 'info');
			mkdir($this->gallery_big, 0700, true);
		}
		if (!is_writable($this->gallery_big)) {
			$_logger->log('gallery_big directory not writable by user', 'error');
			return false;
		}
		if (!is_dir($this->gallery_small)) {
			$_logger->log('attempting to create gallery_small: '.myRealPath($this->gallery_small), 'info');
			mkdir($this->gallery_small, 0700, true);
		}
		if (!is_writable($this->gallery_small)) {
			$_logger->log('gallery_small directory not writable by user', 'error');
			return false;
		}
		if ($this->layout!='internal' && (!is_file($this->layout) || !is_readable($this->layout))) {
			$_logger->log('template file not readable by user', 'error');
			$_logger->log('tried: '.myRealPath($this->layout), 'error');
			return false;
		}
		if (!is_numeric($this->dim_big['width']) || !is_numeric($this->dim_big['height']) ||
			!is_numeric($this->dim_small['width']) || !is_numeric($this->dim_small['height'])) {
			$_logger->log('dimensions are not numeric', 'error');
			return false;
		}
		if (!is_numeric($this->items) || $this->items < 1) {
			$_logger->log('items per page not numeric', 'error');
			return false;
		}

		return true;
	}

	// Validate and return parameters.
	private function _get_params()
	{
		global $_logger;

		$query = urldecode($_SERVER['QUERY_STRING']);
		$page = preg_replace('#.*/(\d+)$#', '\\1', $query);
		$query = preg_replace('#(.*)/\d+$#', '\\1', $query);

		if (is_numeric($page) && $page>=1 && $page<=100) {
			$this->page=$page;
		}
		if (empty($query))
			return array('browse', '', null);

		if (!validate::source_file($this->images, $this->images.'/'.urldecode($query))) {
			$_logger->log("not a valid source file '{$this->base}' '{$this->images}/{$query}'", 'error');
			return false;
		}

		// clean the variable, this prevents double caching
		$images = myRealPath($this->images);
		$query = myRealPath($this->images.'/'.urldecode($query));
		$query = substr($query, strlen($images)+1);

		if (is_dir($this->images.'/'.$query)) {
			if (!validate::authenticate_dir($query))
				return array('login', $query, null);
			return array('browse', $query, null);
		}

		if (is_file($this->images.'/'.$query)) {
			$dir = (dirname($query)=='.')?'':dirname($query);
			if (!validate::authenticate_dir(dirname($query)))
				return array('login', $dir, basename($query));
			return array('image', $dir, basename($query));
		}

		$_logger->log('Not a file nor directory! '.$query, 'error');
		return false;
	}

	// Read directory contents and filter out unnecessary stuff.
	private function readdir($dirname, $shadow_dir=null)
	{
		global $_logger;

		if (!$shadow_dir) {
			$shadow_dir=$dirname;
		}
		$types = array(
			'jpeg'	=> 'image',
			'jpg'	=> 'image',
			'gif'	=> 'image',
			'png'	=> 'image',
			/* MPEG-4 video */
			'mp4'	=> 'video',
			'm4a'	=> 'video',
			'm4p'	=> 'video',
			'm4b'	=> 'video',
			'm4r'	=> 'video',
			'm4v'	=> 'video',
			/* OGG video */
			'ogg'	=> 'video',
			'ogv'	=> 'video',
			'oga'	=> 'video',
			'ogx'	=> 'video',
			'spx'	=> 'video',
			'opus'	=> 'video',
			/* WebM Video */
			'webm'	=> 'video'
		);

		$directories=array();
		$files=array();
		if (($handle = opendir($dirname)) === false) {
			$_logger->log("Failed to open directory! $dirname", 'error');
			return false;
		}

		while ($foo = readdir($handle))
		{
			if ($foo == '.' || $foo == '..')
				continue;
			if (is_dir("$shadow_dir/$foo")) {
				$stats = stat("$shadow_dir/$foo");
				$directories[] = array('name'=>$foo, 'stats'=>$stats);
				continue;
			}
			if (is_file("$shadow_dir/$foo")) {
				foreach ($types as $ext => $mediatype) {
					if (preg_match("/\.${ext}$/i", $foo)) {
						$files[]=array('name'=>$foo, 'mediatype'=>$mediatype);
						continue 2;
					}
				}
			}
		}
		sort($directories);
		sort($files);
		return array($directories, $files);
	}

	// Main function. Initialize everything.
	function initialize()
	{
		global $_logger;

		$xml=new stdClass;
		$xml->author=AUTHOR;
		$xml->program_version=VERSION;

		error_reporting(E_ALL);
		ini_set('display_errors', 0);
		ini_set('log_errors', 1);

		if (!$this->load_configuration()) {
			$_logger->log('Config failed!', 'error');
			return false;
		}

		$img	= new image();
		$pager	= new pager();

		if (($temp = $this->_get_params())===false) {
			$_logger->log('Parameters failed!', 'error');
			return false;
		}
		list($mode, $dir, $file) = $temp;

		// This could be done in pager class
		$count=0;
		$temp='';
		$paths=explode('/', $dir);
		$xml->statusline = new stdClass;
		$xml->statusline->directories=array();
		foreach ($paths as $path) {
			$xml->statusline->directories[$count] = new stdClass;
			$xml->statusline->directories[$count]->name=$path;
			$xml->statusline->directories[$count++]->link=$temp.$path;
			$temp.=$path.'/';
		}

		switch ($mode)
		{
			case "logout":
			{
				validate::reset_authorizations();
				header("Location: ".$_SERVER['PHP_SELF']);
				die();
			} break;
			case "login":
			{
				if (isset($_POST['datasubmitted'])) {
					if (isset($_POST['user'])&&isset($_POST['pass'])&&validate::login($dir, $_POST['user'], $_POST['pass'])) {
						validate::set_authorizations($_POST['user']);
						header("Location: ".$_SERVER['PHP_SELF']."?".$dir.'/'.$file);
						die();
					}
					$xml->info->loginfailed='true';
				}
				$xml->showlogin='true';
			} break 1;
			case "browse":
			default:
			{
				// Paging.
				if (($temp=$this->readdir("{$this->images}/$dir"))===false) {
					return false;
				}
				list($directories, $files) = $temp;
				$pager->init(count($files), $this->items, $this->page);
				$xml->paging=$pager->genlinks();

				// FIXME: error checking
				$wdir = $this->gallery_small.'/'.$dir;
				if (!is_dir($wdir))
					mkdir($wdir, 0700, true);
				if (!is_dir($wdir) || !is_writable($wdir)) {
					$_logger->log('mkdir failed: '.$wdir, 'error');
					return false;
				}
				$img->set_thumbnail_maximums($this->dim_small['width'], $this->dim_small['height']);
				$img->set_thumbnail_dir($this->gallery_small.'/'.$dir);

				$interval=2678400; // 31 days
				$xml->directories=array();
				$time=time();
				foreach ($directories as $current)
				{
					$name=$current['name'];
					$xml->directories[$name] = new stdClass;
					$xml->directories[$name]->name=$name;
					$xml->directories[$name]->time=strftime('%Y-%m-%d %H:%M', $current['stats']['mtime']);
					$xml->directories[$name]->link=empty($dir)?$name:"$dir/$name";
					if (!validate::authenticate_dir(empty($dir)?$name:"$dir/$name"))
						$xml->directories[$name]->status='locked';
					if ($current['stats']['mtime'] > $time-($this->hiliting*24*3600)) {
						$xml->directories[$name]->hilite='true';
					}
				}
				$offset=($this->page-1)*$this->items;
				$xml->thumbnails=array();
				for ($i=$offset; $i<$offset+$this->items; $i++)
				{
					// "End of Media"
					if (!isset($files[$i]))
						break 1;

					$img->set_file("{$this->images}/$dir/{$files[$i]['name']}");
					$name=$files[$i]['name'];
					if ($files[$i]['mediatype'] == "image") {
						$thumbnail=$img->generate_thumb();
						// FIXME error checking
						$thumbnail=substr($thumbnail, strlen(IMGDST2));
					}
					$xml->thumbnails[$name] = new stdClass;
					$xml->thumbnails[$name]->name=$name;
					$xml->thumbnails[$name]->mediatype=$files[$i]['mediatype'];
					if ($files[$i]['mediatype'] == "video") {
						$xml->thumbnails[$name]->width = $this->dim_small['width'];
						$xml->thumbnails[$name]->height = $this->dim_small['height'];
						$xml->thumbnails[$name]->thumbnail = 'wrapper.php?video'.'/'.$dir.'/'.$name;
					} else {
						$xml->thumbnails[$name]->thumbnail='wrapper.php?small'.$thumbnail;
					}
					$xml->thumbnails[$name]->link=empty($dir)?$files[$i]['name']:"$dir/{$files[$i]['name']}";

				}
			} break 1;
			case "image":
			{
				// Paging.
				// FIXME: move this into the pager class
				$xml->paging=array();
				if (($temp=$this->readdir("{$this->images}/$dir"))===false) {
					return false;
				}

				list(/*$directories*/, $files) = $temp;
				for ($index=0; ; $index++)
					if ($file == $files[$index]['name'])
						break;

				for ($i=$index-1; $i<$index+2; $i++)
				{
					if (!isset($files[$i]['name']))
						continue 1;

					$link = empty($dir)?$files[$i]['name']:"$dir/{$files[$i]['name']}";

					$xml->paging[$i] = new stdClass;
					$xml->paging[$i]->name=$files[$i]['name'];
					if ($files[$i]['name']!=$files[$index]['name']) {
						if ($i == $index-1) {
							$xml->paging[$i]->lid='link_previous';
						} else {
							$xml->paging[$i]->lid='link_next';
						}
						$xml->paging[$i]->link=$link;
					}
				}

				$wdir = $this->gallery_big.'/'.$dir;
				if (!is_dir($wdir))
					mkdir($wdir, 0700, true);

				if (!is_dir($wdir) || !is_writable($wdir)) {
					$_logger->log('mkdir failed: '.$wdir, 'error');
					return false;
				}

				if ($files[$index]['mediatype'] == "video") {
					// Without set_thumbnail_dir $img->compare_cache will error
					$img->set_thumbnail_dir($this->gallery_big.'/'.$dir);
					$xml->showvideo = new stdClass;
					$xml->showvideo->video = 'wrapper.php?video'.'/'.$dir.'/'.$file;
					$xml->showvideo->width = $this->dim_big['width'];
					$xml->showvideo->height = $this->dim_big['height'];
					$xml->showvideo->description=$file;
				} else {
					$img->set_thumbnail_maximums($this->dim_big['width'], $this->dim_big['height']);
					$img->set_thumbnail_dir($this->gallery_big.'/'.$dir);
					$img->set_file($this->images.'/'.$dir.'/'.$file);
					$thumbnail=$img->generate_thumb();
					if ($thumbnail === FALSE) {
						$_logger->log('Failed to create thumbnail', 'error');
						break 1;
					}
					// FIXME error checking
					$thumbnail=substr($thumbnail, strlen(IMGDST1));
					$xml->showimage = new stdClass;
					$xml->showimage->image='wrapper.php?big'.$thumbnail;
					$xml->showimage->description=$file;
					$exif = exif_read_data($this->images.'/'.$dir.'/'.$file);
					if ($exif) {
						if (isset($exif['COMPUTED']) && isset($exif['COMPUTED']['UserComment'])) {
							$xml->showimage->description = $exif['COMPUTED']['UserComment'];
						} else if (isset($exif['COMMENT'])) {
							$xml->showimage->description = '';
							foreach ($exif['COMMENT'] as $comment)
								$xml->showimage->description .= $comment;
						}
					}
				}
			} break 1;
		}
		$img->compare_cache($this->images.'/'.$dir);
		$this->xmlroot->addObject($xml);

		// Write cached results
		return true;
	}

	function output()
	{
		$xsl = new XSLTProcessor();
		$doc = new DOMDocument();
		$doc->load('layout/site.xsl');
		$xsl->importStyleSheet($doc);

		$dom = new DOMDocument();
		$dom = $this->xmlroot->returnDom();

		if (defined('DEVEL')) {
			print('<pre>');
			print(htmlspecialchars($this->xmlroot->__toString()));
			print('</pre>');
		}

		return $xsl->transformToXML($dom);
	}
}	

$_logger = new logger_lite();

$g = new gallery();
if (!$g->initialize())
	print ("Gallery init failed<br>\n");

if (defined('DEVEL')) {
	print ('<pre>'.$_logger->flush().'</pre>');
}
print ($g->output());

?>
