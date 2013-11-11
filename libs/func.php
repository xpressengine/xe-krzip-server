<?php

include 'config.php';

function detectUTF8($string, $return_convert = FALSE, $urldecode = TRUE)
{
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

function checkVersionDateFile() {
	global $path_version_date;

	// Check File is Writable
	$fp = @fopen( $path_version_date, "r+" );
	if( $fp == false ) {
		$fp = @fopen( $path_version_date, "r" );
		if( $fp == false ) {
			echo "File Open ERROR, Check File or Directory : {$path_version_date}";
		} else {
			echo "File Permission ERROR, Check File's Permission : {$path_version_date}";
			fclose( $fp );
		}

		return false;
	}
	fclose( $fp );
	return true;
}

function getVersionDate() {
	global $path_version_date;
	if( empty( $path_version_date ) === true ) {
		echo "Not Defined path-version-date";
		return "";
	}
	$fp = fopen( $path_version_date, "r" );
	$versionDate = trim( fgets( $fp ) );
	fclose( $fp );
	return $versionDate;
}

function setVersionDate( $versionDate ) {
	global $path_version_date;
	if( empty( $path_version_date ) === true ) {
		echo "Not Defined path-version-date";
		return false;
	}
	$fp = fopen( $path_version_date, "w" );
	fwrite( $fp, $versionDate );
	fclose( $fp );
	
	return true;
}

