<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Admin page 
 *
 * @author NAVER (developers@xpressengine.com)
 */
header("Content-Type: text/html; charset= UTF-8 ");

define('__KRZIP_PATH__', dirname(__FILE__)); 

include __KRZIP_PATH__ . "/conf/db.config.php";
include __KRZIP_PATH__ . '/conf/path.config.php';
include __KRZIP_PATH__ . '/libs/func.php';

$mysqli = @new mysqli( __KRZIP_DB_HOST__, __KRZIP_DB_USER__, __KRZIP_DB_PASSWORD__, __KRZIP_DB_DATABASE__, __KRZIP_DB_PORT__ );
if( is_object( $mysqli ) === false ) {
	echo krzipResponse( false, "Error, Check krzip's logs file" ); 
	krzipLog( "Can't connect server : " );
	exit;
}

if( $mysqli->connect_errno ) {
	echo krzipResponse( false, "Error, Check krzip's logs file" ); 
	krzipLog( "Connect error : " . $mysqli->connect_error );
	exit;
}

$mysqli->query("SET NAMES 'utf8'" );

if( isset( $_REQUEST['addr3'] ) ) {
	// 구주소 ( 지번주소 ) 검색
	$addr3 = trim($_REQUEST['addr3']);
	if(!$addr3) exit();

	if(get_magic_quotes_gpc()) $addr3 = stripslashes(str_replace("\\","\\\\",$addr3));
	if(!is_numeric($addr3)) $addr3 = $mysqli->real_escape_string($addr3);

	$query = sprintf("SELECT * FROM kr_zipcode WHERE addr3 LIKE '%s%%'", $addr3);
	$ret = $mysqli->query( $query );
	if( $ret == false ) {
		echo krzipResponse( false, "Error, Check krzip's logs file" ); 
		krzipLog( "Query error : " . $mysqli->error );
		exit;
	}
	while($tmp = $ret->fetch_object()) {
	    $address[] = sprintf("%s %s %s %s (%s)", $tmp->addr1, $tmp->addr2, $tmp->addr3, $tmp->addr4, $tmp->zip);
	}

	echo base64_encode(serialize($address));
	$mysqli->close();
	exit;
} 
else {
	// 신주소 ( 도로명주소 ) 검색
	$strCheck=krzipCheckVersionDateFile();
	if( $strCheck !== true ) {
		echo krzipResponse( false, "Error, Check krzip's logs file" );
		krzipLog( "Config error : " . $strCheck );
		exit;
	}

	$currentVersionDate = krzipGetVersionDate();
	if( $currentVersionDate == "" ) {
		echo krzipResponse( false, "Error, Check krzip's logs file" ); 
		krzipLog( "Config error : Can't found conf/versionData.inc file" );
		exit;
	}


	if( $_GET['request'] == "addr1" ) {
		include __KRZIP_PATH_CACHE_ADDR__ . $currentVersionDate . ".php";
		echo krzipResponse( true, $__KRZIP_ADDR1__ );
		$mysqli->close();
		exit;
	}
	elseif( $_GET['request'] == "addr2" ) {
		include __KRZIP_PATH_CACHE_ADDR__ . $currentVersionDate . ".php";
		$arrKey = array_keys( $__KRZIP_ADDR1__, $_GET['search_addr1'] );
		$keyAddr1 = $arrKey[0];
		echo krzipResponse( true, $__KRZIP_ADDR2__[$keyAddr1] );
		$mysqli->close();
		exit;
	}

	if( empty( $_GET['search_word'] ) ) {
		echo krzipResponse( false, "검색 단어를 입력하세요." );
		$mysqli->close();
		exit;
	}

	$search_addr1 = trim($_GET['search_addr1']);	// 시도 검색
	$search_addr2 = trim($_GET['search_addr2']);	// 신군구 검색
	$search_word = trim($_GET['search_word']);	// 도로명 + 도로번호, 동 + 번지, 건물이름 검색

	if(get_magic_quotes_gpc()) {
		$search_addr1 = stripslashes(str_replace("\\","\\\\",$search_addr1));
		$search_addr2 = stripslashes(str_replace("\\","\\\\",$search_addr2));
		$search_word = stripslashes(str_replace("\\","\\\\",$search_word));
	}

	if(!is_numeric($search_addr1)) $search_addr1 = $mysqli->real_escape_string($search_addr1);
	if(!is_numeric($search_addr2)) $search_addr2 = $mysqli->real_escape_string($search_addr2);
	if(!is_numeric($search_word)) $search_word = $mysqli->real_escape_string($search_word);

	if( function_exists( "mb_strlen" ) ) {
		$arrSearchWord = explode( " ", $search_word );
		if( isset( $arrSearchWord[0] ) && isset( $arrSearchWord[1] ) ) {
			$len = mb_strlen( $arrSearchWord[0], "UTF-8" );
			$lastWord = mb_substr( $arrSearchWord[0], $len-1, 1, "UTF-8" );
			$checkWord = mb_substr( $arrSearchWord[0], $len-2, 1, "UTF-8" );
			if( ( $lastWord == "동" || $lastWord == "리" ) && is_numeric( $checkWord ) == true ) {
				$arrSearchWord[0] = mb_substr( $arrSearchWord[0], 0, $len-2, "UTF-8" ) . mb_substr( $arrSearchWord[0], $len-1, 1, "UTF-8" );
			}
		}
	
		$search_word = join( " ", $arrSearchWord );
	}

	// paging
	$offset = !isset( $_GET['next'] ) ? 0 : $_GET['next'];
	if( !is_numeric( $offset ) ) $offset = 0;
	$limit = !isset( $_GET['limit'] ) ? 20 : $_GET['limit'];

	$address = array();

	$presetSeq = 0;
	if( $offset == 0 ) {
		$query = sprintf( "SELECT * FROM kr_zipcode_v2%s WHERE addr1_1='%s' AND addr1_2='%s' AND ( addr2_new='%s' OR addr2_old='%s' OR bdname='%s' )", $currentVersionDate, $search_addr1, $search_addr2, $search_word, $search_word, $search_word );
		$ret = $mysqli->query( $query );
		if( $ret == false ) {
			echo krzipResponse( false, "Error, Check krzip's logs file" ); 
			krzipLog( "Query error : " . $mysqli->error );
			exit;
		}
		if( $ret ) {
			$tmp = $ret->fetch_object();	
			if( $tmp ) {
				$address[] = krzipSetResult( $tmp );
				$presetSeq = $tmp->seq;
			}
		}
	}

	$query = sprintf( "SELECT * FROM kr_zipcode_v2%s WHERE addr1_1='%s' AND addr1_2='%s' AND ( addr2_new LIKE '%s%%' OR addr2_old LIKE '%s%%' OR bdname LIKE '%s%%' ) ORDER BY addr1_1, addr1_2 LIMIT %s, %s", $currentVersionDate, $search_addr1, $search_addr2, $search_word, $search_word, $search_word, $offset, $limit );
	$ret = $mysqli->query( $query );
	if( $ret == false ) {
		echo krzipResponse( false, "Error, Check krzip's logs file" ); 
		krzipLog( "Query error : " . $mysqli->error );
		exit;
	}
	while( $tmp = $ret->fetch_object() ) {
		// check "seq" already set into $address
		if( $presetSeq == $tmp->seq )
			continue;
		$address[] = krzipSetResult( $tmp );
	}

	$arrRes = array(
		"address"	=>	$address,
		"next"	=>	$offset+$limit,
	);

	// is last page
	if( empty( $address ) || count( $address ) < $limit-1 )
		$arrRes['next'] = -1;

	echo krzipResponse( true, $arrRes );
	$mysqli->close();
	exit;
}

