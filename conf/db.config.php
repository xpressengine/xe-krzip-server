<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * config
 *
 * @author NAVER (developers@xpressengine.com)
 */

// check user's config file
if ( file_exists( dirname(__FILE__) . '/db.config.user.php') ) {
	include dirname(__FILE__) . '/db.config.user.php';	// set to here your database config and others
}

if (!defined('__KRZIP_DB_HOST__')) {
	define('__KRZIP_DB_HOST__', 'your host');
}
if (!defined('__KRZIP_DB_USER__')) {
	define('__KRZIP_DB_USER__', 'your user');
}
if (!defined('__KRZIP_DB_PASSWORD__')) {
	define('__KRZIP_DB_PASSWORD__', 'your password');
}
if (!defined('__KRZIP_DB_PORT__')) {
	define('__KRZIP_DB_PORT__', 'your port');	// default 3306
}
if (!defined('__KRZIP_DB_DATABASE__')) {
	define('__KRZIP_DB_DATABASE__', 'your database');
}
