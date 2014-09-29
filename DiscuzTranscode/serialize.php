<?
/**
 * Database charset convert
 * 
 **/
 
//error_reporting(E_ALL);

$step = isset($_GET['step']) ? $_GET['step'] : 'start';

if($step == 'start') {
	$msg = "<a href='serialize.php?step=convert&type=x2'>Discuz! X2 序列化整理</a>";
	$msg .= "<br/><a href='serialize.php?step=convert&type=x25'>Discuz! X2.5 序列化整理</a>";
	$msg .= "<br/><a href='serialize.php?step=convert&type=uch2'>UCHOME 2.0 序列化整理</a>";
	show_msg($msg);
	
} elseif($step == 'convert') {
	$type = $_GET['type'];
	
	if($type == 'x2' || $type == 'x25') {
		require './config/config_global.php';
		$dbserver   = $_config['db']['1']['dbhost'];
		$dbusername = $_config['db']['1']['dbuser'];
		$dbpassword = $_config['db']['1']['dbpw'];
		$database   = $_config['db']['1']['dbname'];
		$dbcharset  = $_config['db']['1']['dbcharset'];
	} elseif ($type == 'uch2') {
		require '.config.php';
		$dbserver   = $_SC['dbhost'];
		$dbusername = $_SC['dbuser'];
		$dbpassword = $_SC['dbpw'];
		$database   = $_SC['dbname'];
		$dbcharset  = $_SC['dbcharset'];
	}
	
	if($dbcharset == 'gbk')
	    $tocharset = 'utf8';
	else
	    $tocharset = 'gbk';
	
	$limit = 100;
	$nextid = 0;
	
	
	$start = !empty($_GET['start']) ? $_GET['start'] : 0;
	$tid = !empty($_GET['tid']) ? $_GET['tid'] : 0;
	$arr = getlistarray($type);
	
	$field = $arr[intval($tid)];
	$stable = $field[0];
	$sfield = $field[1];
	$sid	= $field[2];
	$special = $field[3];
	
	$mysql_conn = @mysql_connect("$dbserver", "$dbusername", "$dbpassword") or die("Mysql connect is error.");
    mysql_select_db($database, $mysql_conn);
    mysql_query('set names '.$dbcharset);
    if($special) {
		$sql = "SELECT $sfield, $sid FROM $stable WHERE $sid > $start ORDER BY $sid ASC LIMIT $limit";
	} else {
		$sql = "SELECT $sfield, $sid FROM $stable";
	}
    
    $query = mysql_query($sql);
    
	while($values = mysql_fetch_array($query)) {
		if($special)
			$nextid = $values[$sid];
		else
			$nextid = 0;
		$data = $values[$sfield];
		$id   = $values[$sid];
		$data = preg_replace_callback('/s:([0-9]+?):"([\s\S]*?)";/','_serialize',$data);
		$data = addslashes($data);
		mysql_query("UPDATE `$stable` SET `$sfield` = '$data' WHERE `$sid` = '$id'", $mysql_conn);
	}
	if($nextid)
	{
		show_msg($stable." $sid > $nextid", "serialize.php?step=convert&type=$type&tid=$tid&start=$nextid");
	}
	else
	{	
		$tid++;
		if($tid < count($arr))
			show_msg($stable." $sid > $nextid", "serialize.php?step=convert&type=$type&tid=$tid&start=0");
		else
			show_msg('转换结束', "serialize.php?step=end");
	
	}
	mysql_close($mysql_conn);
} elseif( $step == 'end') {
	show_msg('整理结束');
}

function _serialize($str) {
	$l = strlen($str[2]);
	return 's:'.$l.':"'.$str[2].'";';
}

function show_msg($message, $url_forward='', $time = 10, $noexit = 0) {
	if(!empty($url_forward)) {
		$message = "<a href=\"$url_forward\">$message (跳转中...)</a><script>setTimeout(\"window.location.href ='$url_forward';\", $time);</script>";
	}
	print<<<END
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>论坛序列化整理工具</title>
	<body>
	<table>
	<tr><td>$message</td></tr>
	</table>
	</body>
	</html>
END;
}

function getlistarray($type) {
	if($type == 'uch2') {
		$list = array(
			array('uchome_data', 'datavalue', 'var', FALSE),
			array('uchome_blogfield', 'tag', 'blogid', TRUE),
			array('uchome_feed', 'body_template', 'feedid', TRUE),
			array('uchome_feed', 'body_data', 'feedid', TRUE),
			array('uchome_report', 'uids', 'rid', TRUE),
			array('uchome_share', 'body_data', 'sid', TRUE),
			array('uchome_userblock', 'blockinfo', 'bid', TRUE),
		);
	} elseif($type == 'x2') {
		$list = array(
				array('pre_common_setting','svalue', 'skey', FALSE),
				array('pre_forum_grouplevel','creditspolicy','levelid', TRUE),
				array('pre_forum_grouplevel','postpolicy','levelid', TRUE),
				array('pre_forum_grouplevel','specialswitch','levelid', TRUE),
				array('pre_common_advertisement','parameters','advid', TRUE),
				array('pre_common_plugin','modules','pluginid', TRUE),
				array('pre_common_block','param','bid', TRUE),
				array('pre_common_block_item','fields','itemid', TRUE),
				array('pre_common_block_style','template','styleid', TRUE),
				array('pre_common_diy_data','diycontent','targettplname', TRUE),
				array('pre_common_member_field_forum','groups','uid', TRUE),
				array('pre_common_member_stat_search','condition','optionid', TRUE),
				array('pre_common_syscache','data','cname', TRUE),
			);
	} elseif($type == 'x25') {
		$list = array(
				array('pre_common_setting','svalue', 'skey', FALSE),
				array('pre_forum_grouplevel','creditspolicy','levelid', TRUE),
				array('pre_forum_grouplevel','postpolicy','levelid', TRUE),
				array('pre_forum_grouplevel','specialswitch','levelid', TRUE),
				array('pre_common_advertisement','parameters','advid', TRUE),
				array('pre_common_plugin','modules','pluginid', TRUE),
				array('pre_common_block','param','bid', TRUE),
				array('pre_common_block_item','fields','itemid', TRUE),
				array('pre_common_block_style','template','styleid', TRUE),
				array('pre_common_diy_data','diycontent','targettplname', TRUE),
				array('pre_common_member_field_forum','groups','uid', TRUE),
				array('pre_common_member_stat_search','condition','optionid', TRUE),
				array('pre_common_syscache','data','cname', TRUE),
			);
	}
	return $list;
}

