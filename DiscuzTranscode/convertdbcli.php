<?php
/**
 * Discuz X DB charset convert
 * 
 **/

require './config/config_global.php';

$step = isset($_GET['step']) ? $_GET['step'] : 'start';

$dbserver   = $_config['db']['1']['dbhost'];
$dbusername = $_config['db']['1']['dbuser'];
$dbpassword = $_config['db']['1']['dbpw'];
$database   = $_config['db']['1']['dbname'];
$dbcharset  = $_config['db']['1']['dbcharset'];

if($dbcharset == 'gbk')
    $tocharset = 'utf8';
else
    $tocharset = 'gbk';


$mysql_conn = @mysql_connect("$dbserver", "$dbusername", "$dbpassword") or die("Mysql connect is error.");
mysql_select_db($database, $mysql_conn);
$table_result = mysql_query('show tables', $mysql_conn);

//get tables
while ($row = mysql_fetch_array($table_result)) {
	$tables[] = $row[0];
}
foreach($tables as $t) {
	echo "ALTER TABLE $t start!\n";
	$sql = 'ALTER TABLE '.$t.' CONVERT TO CHARACTER SET '.$tocharset;
	mysql_query($sql);
	echo "ALTER TABLE $t done!\n";
}
mysql_close($mysql_conn);
echo "Done";

?>

