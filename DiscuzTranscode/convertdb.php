<?php
/**
 * Discuz X DB charset convert
 **/

//error_reporting(E_ALL);
require './config/config_global.php';

$step = isset($_GET['step']) ? $_GET['step'] : 'start';

$dbserver   = $_config['db']['1']['dbhost'];
$dbusername = $_config['db']['1']['dbuser'];
$dbpassword = $_config['db']['1']['dbpw'];
$database   = $_config['db']['1']['dbname'];
$dbcharset  = $_config['db']['1']['dbcharset'];

if(empty($_GET['from']) && empty($_GET['to'])) {
	if($dbcharset == 'gbk')
	    $tocharset = 'utf8';
	else
	    $tocharset = 'gbk';
} else {
	$dbcharset = $_GET['from'];
	$tocharset = $_GET['to'];
}

if($step == 'start') {
    $message = '注意：转换程序会调用DiscuzX!的配置文件config_global.php。转换程序会转换当前库中的所有表格。<br/>当前编码:'.$dbcharset.'<br/>要转换编码:'.$tocharset.'<br/><a href="convertdb.php?step=convertdb&nextdb=0">点击开始</a>';
    show_msg_body($message);
} elseif( $step == 'convertdb') {
    $currentdb = $_GET['nextdb'];
    $mysql_conn = @mysql_connect("$dbserver", "$dbusername", "$dbpassword") or die("Mysql connect is error.");
    mysql_select_db($database, $mysql_conn);
    $table_result = mysql_query('show tables', $mysql_conn);

    //get tables
    while ($row = mysql_fetch_array($table_result)) {
        $tables[] = $row[0];
    }
    $sql = 'ALTER TABLE '.$tables[$currentdb].' CONVERT TO CHARACTER SET '.$tocharset;
    echo $sql;
    mysql_query($sql);
    mysql_close($mysql_conn);
    $currentdb++;
    if($currentdb < count($tables))
        $redirect = "convertdb.php?step=convertdb&from=$dbcharset&to=$tocharset&nextdb=$currentdb";
    else
        $redirect = "convertdb.php?step=end";
    show_msg_body('数据库编码转换', $redirect);
} elseif( $step == 'end') {
    show_msg_body('数据库转换完成，请进行序列化转换');
}

function show_msg_body($message, $url_forward='', $time = 1, $noexit = 0) {
	if(!empty($url_forward)) {
		$url_forward = !empty($_GET['from']) ? $url_forward.'&from='.rawurlencode($_GET['from']) : $url_forward;
		$message = "<a href=\"$url_forward\">$message (跳转中...)</a><script>setTimeout(\"window.location.href ='$url_forward';\", $time);</script>";
	}
	print<<<END
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>数据库转码工具</title>
	<body>
	<table>
	<tr><td>$message</td></tr>
	</table>
	</body>
	</html>
END;
}

?>

