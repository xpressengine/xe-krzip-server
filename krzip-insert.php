<?php

include 'config.php';
include 'func.php';

/*
!!!! UPDATE ONLY new type zipcode  !!!!
*/

// DB
$conn = mysql_connect( $db_host, $db_user, $db_password );
mysql_select_db( $db_database, $conn );
mysql_query( "set charset utf8" );

if( checkVersionDateFile() === false )
	exit;

// check current version
$currentVersionDate = getVersionDate();

// check file
$lFileName = array();
$nextVersionDate = "";
$filePath = $path_dst_data;
$dirHandler = opendir( $filePath );
if( $dirHandler == false ) {
	echo "오류 : 우편번호 적용 대상파일 폴더가 존재하지 않습니다.\n";
	exit;
}

while( false !== ( $fileName = readdir( $dirHandler ) ) ) {
	$checkDate = "";
	$pathInfo = pathInfo( $filePath . "/" . $fileName );

	// File Check
	if( isset( $pathInfo['extension'] ) == false || $pathInfo['extension'] != "txt" ) 
		continue;
	

	$aFileName = explode( "_", $pathInfo['filename'] );

	// File Name Check.. File Name Must Start to date
	$checkDateType = mktime( 0, 0, 0, substr( $aFileName[0], 4, 2 ), substr( $aFileName[0], 6, 2 ), substr( $aFileName[0], 0, 4 ) );
	$compareDate = date( "Ymd", $checkDateType );
	if( $aFileName[0] != $compareDate ) {
		echo "오류 : 우편번호 적용 대상 파일 폳더에 여러개 날짜의 파일이 존재합니다. 대상 폴더의 파일을 확인해 주세요.";
		exit;
	}

	$nextVersionDate = $compareDate;
	array_push( $lFileName, $filePath . "/" . $fileName );
}

if( empty( $lFileName ) === true ) {
	echo "오류 : 실행가능한 파일이 없습니다\n";
	exit;
}

// date Type check
if( $nextVersionDate == "" ) {
	echo "오류 : \n";
	exit;
}

// set new table
$sql = "SHOW TABLES LIKE 'kr_zipcode_v2{$nextVersionDate}'";
$row = mysql_fetch_assoc( mysql_query( $sql ) );
if( $row ) {
	echo "Version {$nextVersionDate} is Already Exists\n";
	exit;
}

$sql = "CREATE TABLE `kr_zipcode_v2{$nextVersionDate}` (
`seq` int(12) NOT NULL auto_increment,
`addr1` varchar( 100 ) NOT NULL default '',
`zipcode1` varchar(3) NOT NULL default '',
`zipcode2` varchar(3) NOT NULL default '',
`search_new` varchar( 100 ) NOT NULL default '',
`search_old` varchar( 100 ) NOT NULL default '',
`search_bdname` varchar( 100 ) NOT NULL default '',
`add_info` varchar(100) NOT NULL default '',
`bdname` varchar(100) NOT NULL default '',
PRIMARY KEY  (`seq`),
KEY search_new ( `search_new` ),
KEY search_old ( `search_old` ),
KEY search_bdname( `search_bdname` )
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
";
mysql_query( $sql );

// Get Files Data
foreach( $lFileName as $fileName ) {
	$fp = fopen( $fileName, "r" );
	$checkCharSet = fgets( $fp );	// Each File's First Line Not Use, Move To Next Line
	$fIconv = false;
	if( detectUTF8( $checkCharSet ) !== true )
		$fIconv = true;
	$arrInsertValue = array();
	while( !feof( $fp ) ) {
		$str = trim( fgets( $fp ) );
		if( $fIconv ) 
			$str = iconv( "CP949", "utf8", $str );
		$arrStr = explode( "|", $str );
		if( isset( $arrStr[2] ) === false )
			continue;

		for( $i=0; $i< count( $arrStr ); $i++ )
			$arrStr[$i] = mysql_real_escape_string( trim( $arrStr[$i] ) );

		$zipcode = $arrStr[0];
		$sido = $arrStr[2];
		$sigugun = $arrStr[4];
		$myun = $arrStr[6];
		$streetName = $arrStr[9];
		$streetNum1 = $arrStr[12];
		$streetNum2 = $arrStr[13];
		$bdname = $arrStr[16];
		
		$dong = $arrStr[18];
		$ri = $arrStr[19];
		$groundNum1 = $arrStr[21];
		$groundNum2 = $arrStr[23];
		

		// 공통으로 사용되는 문자열들
		$zipcode1 = substr( $zipcode, 0, 3 );
		$zipcode2 = substr( $zipcode, 3, 6 );

		$arrAddr1 = array();
		if( $sido != "" )
			array_push( $arrAddr1, $sido );
		if( $sigugun != "" )
			array_push( $arrAddr1, $sigugun );
		if( $myun != "" )
			array_push( $arrAddr1, $myun );
		$addr1 = implode( " ", $arrAddr1 ); 

		// 신규주소 노출 시 추가되서 보여줄 정보 정의
		$arrAddName = array();
		if( $dong != "" )
			array_push( $arrAddName, $dong );
		if( $ri != "" )
			array_push( $arrAddName, $ri );
		if( $bdname != "" )
			array_push( $arrAddName, $bdname );

		// 검색에 필요한 필드 및 그에 따르는 정보들 정리
		$arrBDNum = array();
		if( $streetNum1 != "" && $streetNum1 != "0" )
			array_push( $arrBDNum, $streetNum1 );
		if( $streetNum2 != "" && $streetNum2 != "0" )
			array_push( $arrBDNum, $streetNum2 );
		
		$arrSearchNew = array();
		if( $streetName != "" )
			array_push( $arrSearchNew, $streetName );
		if( $arrBDNum )
			array_push( $arrSearchNew, implode( "-", $arrBDNum ) );
		$strSearchNew = implode( " ", $arrSearchNew );

		$arrGroundNum = array();
		if( $groundNum1 != "" && $groundNum1 != "0" )
			array_push( $arrGroundNum, $groundNum1 );
		if( $groundNum2 != "" && $groundNum2 != "0" )
			array_push( $arrGroundNum, $groundNum2 );

		$arrSearchOld = array();
		if( $dong != "" )
			array_push( $arrSearchOld, $dong );
		if( $ri != "" )
			array_push( $arrSearchOld, $ri );
		if( $arrGroundNum )
			array_push( $arrSearchOld, implode( "-", $arrGroundNum ) );
		$strSearchOld = implode( " ", $arrSearchOld );

		$strSearchBDName = str_replace( " ", "", $bdname );
		$strDongName = $dong;
		$strBDName = $bdname;

		if( count( $arrInsertValue ) < 500 ) {
			array_push( $arrInsertValue, "( '{$addr1}','{$zipcode1}','{$zipcode2}','{$strSearchNew}', '{$strSearchOld}', '{$strSearchBDName}','{$strDongName}', '{$strBDName}')" );
		} else {
			$strInsertValues = implode( ",", $arrInsertValue );
			$sql = "INSERT INTO kr_zipcode_v2{$nextVersionDate} (addr1, zipcode1, zipcode2, search_new, search_old, search_bdname, add_info, bdname ) VALUES {$strInsertValues} ";
			mysql_query( $sql );

			$arrInsertValue = array();	// reset
		}
	}

	if( empty( $arrInsertValue ) === false ) {

		$strInsertValues = implode( ",", $arrInsertValue );
		$sql = "INSERT INTO kr_zipcode_v2{$nextVersionDate} (addr1, zipcode1, zipcode2, search_new, search_old, search_bdname, add_info, bdname ) VALUES {$strInsertValues} ";
		mysql_query( $sql );
	}
	
	fclose( $fp );
}

setVersionDate( $nextVersionDate );

if( $currentVersionDate ) {
	$sql = "DROP TABLE kr_zipcode_v2{$currentVersionDate}";
	mysql_query( $sql );
}

mysql_close( $conn );



