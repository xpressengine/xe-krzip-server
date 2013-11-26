<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * krzip-insert zipcode text file insert to database 
 *
 * @author NAVER (developers@xpressengine.com)
 */

if (PHP_SAPI != "cli") {
    exit;
}

// remove time limit 
@set_time_limit(0);

define('__KRZIP_PATH__', realpath( dirname(__FILE__) . "/../" ));

include __KRZIP_PATH__ . "/conf/db.config.php";
include __KRZIP_PATH__ . '/conf/path.config.php';
include __KRZIP_PATH__ . '/libs/func.php';

ob_start();
echo "KRZIP insert client 를 시작합니다.\n";
ob_flush();
flush();

$mysqli = @new mysqli( __KRZIP_DB_HOST__, __KRZIP_DB_USER__, __KRZIP_DB_PASSWORD__, __KRZIP_DB_DATABASE__);
if( is_object( $mysqli ) === false ) {
        echo "Can't connect server : " . $mysqli->error;
        exit;
}

if( $mysqli->connect_errno ) {
        echo "Connect error : " . $mysqli->connect_error;
        exit;
}

$mysqli->query("SET NAMES 'utf8'" );

$strCheck = krzipCheckVersionDateFile();
if( $strCheck !== true ) {
	echo "오류 : " . __KRZIP_PATH_VERSION_DATE__ . " 파일이 존재 하지 않거나 쓰기가 금지 되어 있습니다.\n{$strCheck}";
	exit;
}

$currentVersionDate = krzipGetVersionDate();

// check file
$lFileName = array();
$nextVersionDate = "";
$filePath = __KRZIP_PATH_FILES__;
$dirHandler = opendir( $filePath );
if( $dirHandler == false ) {
	echo "오류 : 우편번호 적용 대상파일 폴더가 존재하지 않습니다.";
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
		echo "오류 : 우편번호 적용 대상 파일 폳더에 여러 날짜의 파일이 존재합니다. 대상 폴더의 파일을 확인해 주세요";
		exit;
	}

	$nextVersionDate = $compareDate;
	$lFileName[] = $filePath . "/" . $fileName;
}

if( empty( $lFileName ) === true ) {
	echo "오류 : 실행가능한 파일이 없습니다";
	exit;
}

// date Type check
if( $nextVersionDate == "" ) {
	echo "오류 : 파일에 버전 날짜를 확인 할 수 없습니다.";
	exit;
}

$fpCache = @fopen( __KRZIP_PATH_CACHE_ADDR__ . $nextVersionDate . ".php", "w" );
if( is_resource( $fpCache ) === false ) {
	echo "캐시 파일을 생성 할 수 없습니다. " . __KRZIP_PATH_CACHE_ADDR__ . $nextVersionDate ." 파일 경로에 폴더와 파일 권한을 확인하세요.";
	exit;
}
chmod( __KRZIP_PATH_CACHE_ADDR__ . $nextVersionDate . ".php", 0777 );

// set new table
$query = "SHOW TABLES LIKE 'kr_zipcode_v2{$nextVersionDate}'";
$row = $mysqli->query( $query )->fetch_object();
if( $row ) {
	echo "오류 : kr_zipcode_v2{$nextVersionDate} 테이블이 이미 존재 합니다.";
	exit;
}
// 새로운 버전의 테이블 생성
$query = "CREATE TABLE `kr_zipcode_v2{$nextVersionDate}` (
  `seq` int(12) NOT NULL AUTO_INCREMENT,
  `addr1_1` varchar(20) NOT NULL DEFAULT '',
  `addr1_2` varchar(50) NOT NULL DEFAULT '',
  `addr1_3` varchar(50) NOT NULL DEFAULT '',
  `zipcode` varchar(7) NOT NULL DEFAULT '',
  `addr2_new` varchar(100) NOT NULL DEFAULT '',
  `addr2_old` varchar(100) NOT NULL DEFAULT '',
  `bdname` varchar(100) NOT NULL DEFAULT '',
  `addinfo` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY  (`seq`),
  KEY addr1 ( `addr1_1`, `addr1_2` ),
  KEY addr2_new ( `addr2_new` ),
  KEY addr2_old ( `addr2_old` ),
  KEY bdname ( `bdname` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

if( ($ret = $mysqli->query( $query )) == false ) {
	echo "테이블 생성에 실패 했습니다.";
	exit;
} 

// insert 하기 위한 대상 파일을 불러옴
$separator = "|";
$arrCacheAddr1 = array();
$arrCacheAddr2 = array();

echo "데이터 입력을 시작합니다.\n";
ob_flush();
flush();
$cLine = 0;
foreach( $lFileName as $fileName ) {
	$fp = fopen( $fileName, "r" );
	$checkCharSet = fgets( $fp );	// 각 파일의 첫번째 줄은 건너뜀
	// UTF8 인코딩이 아닌 파일 확인
	$fIconv = false;
	if( krzipDetectUTF8( $checkCharSet ) !== true )
		$fIconv = true;

	$arrInsertValue = array();
	while( !feof( $fp ) ) {
		$str = trim( fgets( $fp ) );

		// UTF8 인코딩이 아닌 파일에 대한 처리
		if( $fIconv ) 
			$str = iconv( "CP949", "utf8//IGNORE", $str );

		$arrStr = explode( $separator, $str );
		// 정상 문자열 확인
		if( isset( $arrStr[2] ) === false )
			continue;

		for( $i=0,$c=count( $arrStr ); $i< $c; $i++ )
			$arrStr[$i] = $mysqli->real_escape_string( trim( $arrStr[$i] ) );

		$zipcode = $arrStr[0];	// 우편번호
		$addr1 = $arrStr[2];	// 시도
		$addr2 = $arrStr[4];	// 시구군
		$myun = $arrStr[6];	// 면
		$streetName = $arrStr[9];	// 도로 이름
		$streetNum1 = $arrStr[12];	// 도로 번호1
		$streetNum2 = $arrStr[13];	// 도로 번호2
		$bdname = $arrStr[16];	// 건물 이름
		
		$dong = $arrStr[18];	// 동 이름
		$ri = $arrStr[19];	// 리 이름
		$groundNum1 = $arrStr[21];	// 지번 번호1
		$groundNum2 = $arrStr[23];	// 지번 번호2

		if( in_array( $addr1, $arrCacheAddr1 ) == false )
			$arrCacheAddr1[] = $addr1; 

		$arrCurKey = array_keys( $arrCacheAddr1, $addr1 );
		$cacheKey = $arrCurKey[0];
		if( $cacheKey !== false ) {
			if( isset( $arrCacheAddr2[$cacheKey] ) === false )
				$arrCacheAddr2[$cacheKey] = array();
			if( in_array( $addr2, $arrCacheAddr2[$cacheKey] ) === false )
				$arrCacheAddr2[$cacheKey][] = $addr2;
		}

		$zipcode = substr( $zipcode, 0, 3 ) . "-" . substr( $zipcode, 3, 6 );
		$arrStreetNum = array();
		if( $streetNum1 != "" && $streetNum1 != "0" )
			$arrStreetNum[] = $streetNum1;
		if( $streetNum2 != "" && $streetNum2 != "0" )
			$arrStreetNum[] = $streetNum2;

		$arrAddr3New = array();
		if( $streetName != "" )
			$arrAddr3New[] = $streetName;
		if( $arrStreetNum )
			$arrAddr3New[] = join( "-", $arrStreetNum );
		$strAddr3New = join( " ", $arrAddr3New );

		
		$arrGroundNum = array();
		if( $groundNum1 != "" && $groundNum1 != "0" )
			$arrGroundNum[] = $groundNum1;
		if( $groundNum2 != "" && $groundNum2 != "0" )
			$arrGroundNum[] = $groundNum2;

		$arrAddr3Old = array();
		$addInfo = "";
		if( $dong != "" ) {
			$arrAddr3Old[] = $dong;
			$addInfo = $dong;
		}
		if( $ri != "" )
			$arrAddr3Old[] = $ri;
		if( $arrGroundNum )
			$arrAddr3Old[] = join( "-", $arrGroundNum );
		$strAddr3Old = join( " ", $arrAddr3Old );


		$arrInsertValue[] = "('{$addr1}','{$addr2}','{$myun}','{$zipcode}','{$strAddr3New}', '{$strAddr3Old}','{$bdname}',{$addInfo}')";

		if( count( $arrInsertValue ) > 200 ) {
			$strInsertValues = join( ",", $arrInsertValue );
			$query = "INSERT INTO kr_zipcode_v2{$nextVersionDate} (addr1_1, addr1_2, addr1_3, zipcode, addr2_new, addr2_old, bdname, addinfo ) VALUES {$strInsertValues}";
			$mysqli->query( $query );
			$arrInsertValue = array();	// reset
			
			$cLine++;
			echo ".";
			if( $cLine > 200 ) {
				echo "\n";
				$cLine = 0;
			}
			ob_flush();
			flush();
			
		}
	}

	if( empty( $arrInsertValue ) === false ) {
		$strInsertValues = join( ",", $arrInsertValue );
		$query = "INSERT INTO kr_zipcode_v2{$nextVersionDate} (addr1_1, addr1_2, addr1_3, zipcode, addr2_new, addr2_old, bdname, addinfo ) VALUES {$strInsertValues}";
		$mysqli->query( $query );
		$arrInsertValue = array();	// reset
		echo ".";
		ob_flush();
		flush();
	}
	
	fclose( $fp );
}

echo "\n데이터 입력을 완료했습니다. 캐시파일을 만듭니다.\n";
ob_flush();
flush();

fwrite( $fpCache, "<?php\n" );
fwrite( $fpCache, "\$__KRZIP_ADDR1__ = array(\n" );
foreach( $arrCacheAddr1 as $key=>$value ) {
	fwrite( $fpCache, "\"{$key}\"=>\"{$value}\",\n" );
}
fwrite( $fpCache, ");\n\n" );

fwrite( $fpCache, "\$__KRZIP_ADDR2__ = array(\n" );
foreach( $arrCacheAddr1 as $key=>$value ) {
	fwrite( $fpCache, "\"{$key}\"=> array(\n" );
	foreach( $arrCacheAddr2[$key] as $key2=>$value2 ) {
		fwrite( $fpCache, "\"{$key2}\"=>\"{$value2}\",\n" );
	}
	fwrite( $fpCache, "),\n" );
}
fwrite( $fpCache, ");\n" );
fclose( $fpCache );

krzipSetVersionDate( $nextVersionDate );

if( $currentVersionDate != $nextVersionDate ) {
	$query = "DROP TABLE kr_zipcode_v2{$currentVersionDate}";
	$mysqli->query( $query );
}

$mysqli->close();

echo "데이터 적용이 완료되었습니다.";
ob_flush();
flush();
ob_end_flush();

