<?php
/**
 * 生成mysql数据字典
 */

//配置数据库
$dbserver   = "127.0.0.1";
$dbusername = "root";
$dbpassword = "";
if(isset($_GET['d'])){
	$database = $_GET['d'];
} else {
	$database  = "x25dict";
}

//其他配置
$title = 'Discuz! X2.5 数据字典';

$mysql_conn = @mysql_connect("$dbserver", "$dbusername", "$dbpassword") or die("Mysql connect is error.");
mysql_select_db($database, $mysql_conn) or die('Database isn\'t exist.');
mysql_query('SET NAMES utf8', $mysql_conn);
$table_result = mysql_query('show tables', $mysql_conn);
//取得所有的表名
while ($row = mysql_fetch_array($table_result)) {
    $tables[]['TABLE_NAME'] = $row[0];
}

//循环取得所有表的备注及表中列消息
foreach ($tables AS $k=>$v) {
    $sql  = 'SELECT * FROM ';
    $sql .= 'INFORMATION_SCHEMA.TABLES ';
    $sql .= 'WHERE ';
    $sql .= "table_name = '{$v['TABLE_NAME']}'  AND table_schema = '{$database}'";
    $table_result = mysql_query($sql, $mysql_conn);
    while ($t = mysql_fetch_array($table_result) ) {
        $tables[$k]['TABLE_COMMENT'] = $t['TABLE_COMMENT'];
    }

    $sql  = 'SELECT * FROM ';
    $sql .= 'INFORMATION_SCHEMA.COLUMNS ';
    $sql .= 'WHERE ';
    $sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$database}'";

    $fields = array();
    $field_result = mysql_query($sql, $mysql_conn);
    while ($t = mysql_fetch_array($field_result) ) {
        $fields[] = $t;
    }
    $tables[$k]['COLUMN'] = $fields;
}
mysql_close($mysql_conn);


$html = '';
//循环所有表
foreach ($tables AS $k=>$v) {
    //$html .= '<p><h2>'. $v['TABLE_COMMENT'] . '&nbsp;</h2>';
	$html .= '<caption>' . $v['TABLE_NAME'] .'  '. $v['TABLE_COMMENT']. '</caption>';
    $html .= '<table  style="border-collapse:collapse;mso-table-layout-alt:fixed;padding:0.0000pt 5.4000pt 0.0000pt 5.4000pt ; ">';
    $html .= '<tbody><tr style="height:15.6000pt; "><th><span>字段名</span></th><th><span>数据类型</span></th><th><span>默认值</span></th>
    <th><span>允许非空</span></th>
    <th><span>自动递增</span></th><th><span>备注</span></th></tr>';
    $html .= '';

	$line = 0;
    foreach ($v['COLUMN'] AS $f) {
		$line = $line + 1;
		if($line%2 == 1)
			$tdclass='td1';
		else
			$tdclass='td2';

        $html .= '<tr><td class="c1 '.$tdclass.'">' . $f['COLUMN_NAME'] . '</td>';
        $html .= '<td class="c2 '.$tdclass.'">' . $f['COLUMN_TYPE'] . '</td>';
        $html .= '<td class="c3 '.$tdclass.'">&nbsp;' . $f['COLUMN_DEFAULT'] . '</td>';
        $html .= '<td class="c4 '.$tdclass.'">&nbsp;' . $f['IS_NULLABLE'] . '</td>';
        $html .= '<td class="c5 '.$tdclass.'">' . ($f['EXTRA']=='auto_increment'?'是':'&nbsp;') . '</td>';
        $html .= '<td class="c6 '.$tdclass.'">&nbsp;' . $f['COLUMN_COMMENT'] . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></p><br/><br/>';
}

//输出
echo '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.$title.'</title>
<style>
body,td,th {font-family:"微软雅黑"; font-size:12px;}
table{border-collapse:collapse;border:1px solid #CCC;background:#efefef;}
table caption{text-align:left; background-color:#fff; line-height:2em; font-size:14px; font-weight:bold; }
table th{text-align:left; font-weight:bold;height:26px; line-height:26px; font-size:12px; border:1px solid #CCC;background:#4f81bd; border-right:1pt solid #4f81bd; border-top:1pt solid #4f81bd; border-bottom:1pt solid #4f81bd;border-left:1pt solid #4f81bd;}

table th span{color:white; font-weight: bold; font-size:12px; font-family:"微软雅黑"}

.td1{height:20px; font-size:12px; border:1px solid #CCC;background-color:#B8CCE4;border-right:1pt solid #4f81bd; border-top:none; border-bottom:1pt solid #4f81bd;}
.td2{height:20px; font-size:12px; border:1px solid #CCC;background-color:white;border-right:1pt solid #4f81bd; border-top:none; border-bottom:1pt solid #4f81bd;}
.c1{ width: 120px; border-left:1pt solid #4f81bd;}
.c2{ width: 120px;}
.c3{ width: 70px;}
.c4{ width: 80px;}
.c5{ width: 80px;}
.c6{ width: 270px;}
</style>
</head>
<body>';
echo '<h1>'.$title.'</h1>';
echo $html;
echo '</body></html>';

?>
