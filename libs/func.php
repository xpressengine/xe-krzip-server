<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * function library 
 *
 * @author NAVER (developers@xpressengine.com)
 */

/**
* Make jsonp encoded type
*
* @param bool $result This response is normal
* @param mixed $values It will be encode to json type string
* @return string 
*/
function krzipResponse( $result, $values ) {
	$arrRes = array();

	if( is_bool( $result ) === false ) {
		$result = false;
		$values = "response result Not boolean";
	}

	$arrRes['result'] = $result;
	if( is_string( $values ) === true )
		$arrRes['msg'] = $values;
	else
		$arrRes['values'] = $values;

	if(empty($_POST['callback']))
		return json_encode( $arrRes );
	else
		return $_POST['callback'] . "(" . json_encode( $arrRes ) . ")";
}

/**
* Set log
*
* @param string $str Write to log file
* @return bool
*/
function krzipLog( $str ) {
	$time = time();
	$date = date( "Ymd", $time );
	$fileName = __KRZIP_PATH__ . "/logs/krzip-".$date.".log";
	$fp = fopen( $fileName, "a+" );
	if( is_resource( $fp ) === false ) {
		return false;
	}
	fwrite( $fp, date( "H:i:s", $time ) . $str . "\n" );
	fclose( $fp );
	return true;
}

/**
* Check UTF-8
*
* @param string $string
* @param bool $return_convert If set, returns converted string
* @param bool $urldecode
* @return bool
*/
function krzipDetectUTF8($string, $return_convert = FALSE, $urldecode = TRUE) {
        if($urldecode)
        {
                $string = urldecode($string);
        }

        $sample = @iconv('utf-8', 'utf-8', $string);
        $is_utf8 = (md5($sample) == md5($string));

        if(!$urldecode)
        {
                $string = urldecode($string);
        }

        if($return_convert)
        {
                return ($is_utf8) ? $string : iconv('euc-kr', 'utf-8', $string);
        }

        return $is_utf8;
}

/**
* Check version data cache file
*
* @return bool
*/
function krzipCheckVersionDateFile() {
	// Check File is Writable
	$fp = @fopen( __KRZIP_PATH_VERSION_DATE__, "r+" );
	if( $fp == false ) {
		$fp = @fopen( __KRZIP_PATH_VERSION_DATE__, "r" );
		if( $fp == false ) {
			return "File Open ERROR, Check File or Directory : {$path_version_date}";
		} else {
			fclose( $fp );
			return "File Permission ERROR, Check File's Permission : {$path_version_date}";
		}
	}
	fclose( $fp );
	return true;
}

/**
* Get version date from cached file
*
* @return String
*/
function krzipGetVersionDate() {
	$fp = fopen( __KRZIP_PATH_VERSION_DATE__, "r" );
	if( is_resource( $fp ) == false )
		return "";
	$versionDate = trim( fgets( $fp ) );
	fclose( $fp );
	return $versionDate;
}

/**
* Set version date to cache file
*
* return bool
*/
function krzipSetVersionDate( $versionDate ) {
	$fp = fopen( __KRZIP_PATH_VERSION_DATE__, "w" );
	if( is_resource( $fp ) == false )
		return false;
	fwrite( $fp, $versionDate );
	fclose( $fp );
	@chmod( __KRZIP_PATH_VERSION_DATE__, 0777 );
	return true;
}

/**
* mysqli fetch object to array
*
* @param objext $fobj mysqli->query()->fetch_object() , mysqli result object
* @return array
*/
function krzipSetResult( $fobj ) {
	$addr1 = $fobj->addr1_1 . " " . $fobj->addr1_2;
	if( trim( $fobj->addr1_3 ) != "" )
	$addr1 .= " " . $fobj->addr1_3;
	$arrResult = array(
		"seq"	=>	$fobj->seq,
		"addr1" =>	$addr1,
		"addr2_new"	=>	$fobj->addr2_new,
		"addr2_old"	=>	$fobj->addr2_old,
		"bdname"	=>	$fobj->bdname,
		"zipcode"	=>	$fobj->zipcode,
	);

	return $arrResult;
}

/**
* Admin login confirm
*
* @return string
*/
function krzipAuthenticate() {
    header('WWW-Authenticate: Basic realm="XE Simple Admin Authentication"');
    header('HTTP/1.0 401 Unauthorized');
    return "You must enter a valid login ID and password to access this resource. Check your config\n";
}

