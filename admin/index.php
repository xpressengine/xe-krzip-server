<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Admin page
 *
 * @author NAVER (developers@xpressengine.com)
 */

define('__KRZIP_PATH__', realpath( dirname(__FILE__) . "/../" ));

include __KRZIP_PATH__ . "/conf/db.config.php";
include __KRZIP_PATH__ . '/conf/path.config.php';
include __KRZIP_PATH__ . '/libs/func.php';

// 로그인 인증
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != __KRZIP_ADMIN_ID__ || $_SERVER['PHP_AUTH_PW'] != __KRZIP_ADMIN_PW__ ) {
	echo krzipAuthenticate();
	exit;
}

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

// check current set zipcode version
$currentVersionDate = krzipGetVersionDate();
$strCurrentVersion = "";
if( $currentVersionDate == "" ) {
	$strCurrentVersionClass = "notice";
	$strCurrentVersion = "현재 적용된 우편번호가 없습니다.";
} else {
	$strCurrentVersionClass = "";
	$strCurrentVersion = "현재 ". date( "Y년 m월 d일", mktime( 0, 0, 0, substr( $currentVersionDate, 4, 2 ), substr( $currentVersionDate, 6, 2 ), substr( $currentVersionDate, 0, 4 ) ) ) . " 버전의 우편번호를 사용중입니다.";
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
<!-- META -->
<meta charset="utf-8">
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
<style> 
body { font-size:12px; }
.title { font-size:14px; font-weight:bold; }
.contents { margin:0; padding:10px; }
.current-version { border:1px solid #444; padding:10px; }
.notice { background-color:#F15F5F; } 
.info { background-color:#FFF; border:1px solid #353535; margin-top:5px; padding: 2px 5px;}
.desc-detail {padding: 2px 10px; background-color:#CECECE}
.btn {padding:10px;}
</style>
<script type="text/javascript">
//<![CDATA[
var sendKrzipInsert = function() {
	if( confirm( "업로드된 우편번호 파일을 적용하시겠습니까?" ) == false )
		return;
	document.location.replace( "./krzip-insert.php" );
}
//]]>
</script>
</head>
<body>
<div class="title">
KRZIP 주소관리
</div>
<div class="contents">
<div class="current-version <?php echo $strCurrentVersionClass; ?>"><?php echo $strCurrentVersion; ?></div>
<div class="current-files">
	<div class="info">
	<p>* KRZIP 주소관리 사용 법</p>

	<p> 1. http://www.epost.go.kr/ 에서 "지역별 도로명 주소 DB"</p>
	<p class="desc-detail">
	- 메인페이지의 오른쪽에 "우편번호 찾기" 클릭 -> 팝업페이지에서 우측에 "내려받기" 클릭 -> 이동된 페이지에서 "정보활용 유의사항" 체크 후 "지역별 도로명 주소 DB" 다운로드 ( 각 지역별 모든 엑셀 파일을 모두 다운로드 받아야 함 )
	</p>
	 
	<p> 2. 다운로드 받은 파일 압축 해제 </p>
	<p> 3. 주소 텍스트 파일 ( txt 확장자 ) FTP 로 <?php echo __KRZIP_PATH_FILES__;?> 에 업로드 </p>
	<p> 4. 업로드 완료 후 주소 "데이터 적용" 클릭</p>
	</div>

	<div class="btn">
	<button type="button" onclick="sendKrzipInsert();">데이터 적용</button>
	</div>
</div>
</div>
</body>
</html>
