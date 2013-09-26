<?php

/*
 * Don't append slashes for directories,
 * although they don't harm anything it just
 * looks ugly if the variable happens to be
 * used in a context visible to the user
*/


/* Debug messages for developers */

//define ('DEVEL', true);


/*
 * Common settings
 *
 * Defines how recent modifications will
 * be hilited. 0 to disable.
 */
//define ('HILITING', 30);


/*
 * Base directory of the gallery and 
 * directory for images, default is to be
 * relative from BASE. This doesn't have to
 * be visible to the browser, only inside 
 * httpd/php chroot (if there's any)
 */

//define ('BASE', '.');
//define ('IMGSRC', BASE.'/images/orig');


/* Directory where to keep thumbnails */

//define ('IMGDST1', BASE.'/images/big');
//define ('IMGDST2', BASE.'/images/small');


/* Custom template */

//define ('TEMPLATE', BASE.'/layout/layout.tpl');


/* 
 * Thumbnail dimensions, these are maximums!
 * Image proportions are always kept.
 */

//define ('DIMS1', '750x550');
//define ('DIMS2', '200x150');

/* View settings. Items & columns to show */

//define ('ITEMS_PER_PAGE', 15);

/*
 * Simple Access Control List
 *
 * The order is very important, only the
 * first match is checked!
 *
 * Restrictions will be applied recursively.
 *
 * You can have different credentials for different
 * levels by defining the sublevels first, for example:
 *
 * 'album/sublevel' => 'userA',
 * 'album' => 'userB'
 */

/*
 * First assign dir names to a username/group,
 * this is case-SENSITIVE!
 */
//$sacl=array
//(
//	'album1/subalbum1/subalbum2'=>'foouser',
//	'album2/sub2'=>'foouser',
//	'album3'=>'baruser',
//);

/* Then assign a password for that username */
//$sacl_users=array
//(
//	'foouser'	=> array('pw'=>'barpassword',	'can_upload'=>false),
//	'baruser	=> array('pw'=>'quxpass',	'can_upload'=>false),
//);

?>
