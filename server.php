<?php
include 'config.php';
include 'func.php';

$currentVersionDate = getVersionDate();


$addr3 = trim($_REQUEST['addr3']);
if(!$addr3) exit();

$connect = mysql_connect($db_host,$db_user,$db_password) or die(mysql_error());
mysql_select_db($db_database, $connect) or die(mysql_error());
mysql_query("SET NAMES 'utf8'", $connect) or die(mysql_error());

if(get_magic_quotes_gpc()) $addr3 = stripslashes(str_replace("\\","\\\\",$addr3));
if(!is_numeric($addr3)) $addr3 = mysql_real_escape_string($addr3,$connect);

$address = array();

$sql = "SELECT * FROM kr_zipcode_v2{$currentVersionDate} WHERE search_new LIKE '{$addr3}%' ";
$ret = mysql_query( $sql ) or die( mysql_error() );
while( $tmp = mysql_fetch_object( $ret ) ) {

	if( isset( $address[$tmp->seq] ) === true )
		continue;

	$newAddr = sprintf( "%s %s", $tmp->addr1,  $tmp->search_new );
	//$oldAddr = sprintf( "%s %s", $tmp->addr1,  $tmp->search_old );
	$oldAddr = sprintf( "%s", $tmp->search_old );

	if( $tmp->search_bdname != "" ) {
		$newAddr = sprintf( "%s (%s)", $newAddr,   ($tmp->dong_name != "" ) ? "{$tmp->dong_name}, {$tmp->search_bdname}" : $tmp->search_bdname );
	//	$oldAddr = sprintf( "%s %s", $oldAddr,  $tmp->search_bdname );
	}

	$address[$tmp->seq] = "{$newAddr}|{$oldAddr}|{$tmp->zipcode1}-{$tmp->zipcode2}";

//	$address[$tmp->seq] = "{$tmp->addr1} {$tmp->search_new} (구:{$tmp->search_old}) {$tmp->search_bdname}"; 
}

$sql = "SELECT * FROM kr_zipcode_v2{$currentVersionDate} WHERE search_old LIKE '{$addr3}%' ";
$ret = mysql_query( $sql ) or die( mysql_error() );
while( $tmp = mysql_fetch_object( $ret ) ) {

	if( isset( $address[$tmp->seq] ) === true )
		continue;

	$newAddr = sprintf( "%s %s", $tmp->addr1,  $tmp->search_new );
	$oldAddr = sprintf( "%s %s", $tmp->addr1,  $tmp->search_old );

	if( $tmp->search_bdname != "" ) {
		$newAddr = sprintf( "%s (%s)", $newAddr,   ($tmp->dong_name != "" ) ? "{$tmp->dong_name}, {$tmp->search_bdname}" : $tmp->search_bdname );
		$oldAddr = sprintf( "%s %s", $oldAddr,  $tmp->search_bdname );
	}

	$address[$tmp->seq] = "{$newAddr}|{$oldAddr}|{$tmp->zipcode1}-{$tmp->zipcode2}";
}
$sql = "SELECT * FROM kr_zipcode_v2{$currentVersionDate} WHERE search_bdname LIKE '{$addr3}%' ";
$ret = mysql_query( $sql ) or die( mysql_error() );
while( $tmp = mysql_fetch_object( $ret ) ) {

	if( isset( $address[$tmp->seq] ) === true )
		continue;

	$newAddr = sprintf( "%s %s", $tmp->addr1,  $tmp->search_new );
	$oldAddr = sprintf( "%s %s", $tmp->addr1,  $tmp->search_old );

	if( $tmp->search_bdname != "" ) {
		$newAddr = sprintf( "%s (%s)", $newAddr,   ($tmp->dong_name != "" ) ? "{$tmp->dong_name}, {$tmp->search_bdname}" : $tmp->search_bdname );
		$oldAddr = sprintf( "%s %s", $oldAddr,  $tmp->search_bdname );
	}

	$address[$tmp->seq] = "{$newAddr}|{$oldAddr}|{$tmp->zipcode1}-{$tmp->zipcode2}";
}

ksort( $address );

print base64_encode(serialize($address));

