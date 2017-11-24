<?php
/**
 * 生成mysql数据字典
 */

//配置数据库
$dbserver   = "";
$dbusername = "";
$dbpassword = "";

$database = '';

//其他配置
$title = $database.'数据字典';

//$mysql_conn = mysql_connect("$dbserver", "$dbusername", "$dbpassword") or die("Mysql connect is error.");
$mysql_conn = new mysqli("$dbserver","$dbusername", "$dbpassword") or die ("connect error:".mysqli_connect_error());
$mysql_conn->select_db($database) or die('Database isn\'t exist.');
$mysql_conn->query('SET NAMES utf8');
$table_result = $mysql_conn->query('show tables');
//取得所有的表名
while ($row = $table_result->fetch_array(MYSQLI_ASSOC)) {
    $tables[]['TABLE_NAME'] = $row['Tables_in_online_docs'];
}

//循环取得所有表的备注及表中列消息
foreach ($tables AS $k=>$v) {
    $sql  = 'SELECT * FROM ';
    $sql .= 'INFORMATION_SCHEMA.TABLES ';
    $sql .= 'WHERE ';
    $sql .= "table_name = '{$v['TABLE_NAME']}'  AND table_schema = '{$database}'";
    $table_result = $mysql_conn->query($sql);
    while ($t = $table_result->fetch_array() ) {
        $tables[$k]['TABLE_COMMENT'] = $t['TABLE_COMMENT'];
        $tables[$k]['TABLE_COLLATION'] = $t['TABLE_COLLATION'];
        $tables[$k]['ENGINE'] = $t['ENGINE'];
    }

    $sql  = 'SELECT * FROM ';
    $sql .= 'INFORMATION_SCHEMA.COLUMNS ';
    $sql .= 'WHERE ';
    $sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$database}'";

    $fields = array();
    $field_result = $mysql_conn->query($sql);
    while ($t = $field_result->fetch_array() ) {
        $fields[] = $t;
    }
    $tables[$k]['COLUMN'] = $fields;

    $index = array();
    $sql  = "SHOW INDEX FROM $v[TABLE_NAME]"; 
    $result = $mysql_conn->query($sql);
    while($t = $result->fetch_array()) {
        $index[] = $t;
    }
    $tables[$k]['index'] = $index;
}
$mysql_conn->close();
$html = '';
//循环所有表
foreach ($tables AS $k=>$v) {
    //$html .= '<p><h2>'. $v['TABLE_COMMENT'] . '&nbsp;</h2>';
	$html .= '<div class="table_head">' . $v['TABLE_NAME'] .'  '. $v['TABLE_COMMENT']. '</div>';
    $html .= '<div class="table_body">';
    $html .= '<table><tbody>';
    $html .= '<tr style="height:15.6000pt;"><th><span>字符集</span></th><th><span>引擎</span></th></tr>';
    $html .= '<tr><td class="c_md td1">'.$v['TABLE_COLLATION'].'</td><td class="c_md td1">'.$v['ENGINE'].'</td></tr>';
    $html .= '</tbody></table>';
    $html .= '</div>';

    $html .= '<div class="table_body">';
    $html .= '<table>';
    $html .= '<tbody><tr style="height:15.6000pt;">
        <th><span>是否唯一</span></th>
        <th><span>名称</span></th>
        <th><span>索引序号</span></th>
        <th><span>列名称</span></th></tr>';
        foreach($v['index'] as $i) {
            $line = $line + 1;
            $tdclass = $line%2 == 1 ? 'td1' : 'td2';
            $html .= '<tr><td class="c_md '.$tdclass.'">' . $i[1] . '</td>';
            $html .= '<td class="c_bg '.$tdclass.'">' . $i[2] . '</td>';
            $html .= '<td class="c_md '.$tdclass.'">' . $i[3] . '</td>';
            $html .= '<td class="c_md '.$tdclass.'">' . $i[4] . '</td>';
            $html .= '</tr>';
        }
    $html .= '</table>';
    $html .= '</div>';

    $html .= '<div class="table_body">';
    $html .= '<table>';
    $html .= '<tbody><tr style="height:15.6000pt;"><th><span>字段名</span></th>
    <th><span>数据类型</span></th>
    <th><span>默认值</span></th>
    <th><span>允许非空</span></th>
    <th><span>自动递增</span></th>
    <th><span>字符集</span></th>
    <th><span>备注</span></th></tr>';
    $html .= '';

	$line = 0;
    foreach ($v['COLUMN'] AS $f) {
		$line = $line + 1;
		if($line%2 == 1)
			$tdclass='td1';
		else
			$tdclass='td2';

        $html .= '<tr><td class="c_md '.$tdclass.'">' . $f['COLUMN_NAME'] . '</td>';
        $html .= '<td class="c_md '.$tdclass.'">' . $f['COLUMN_TYPE'] . '</td>';
        $html .= '<td class="c_sm '.$tdclass.'">&nbsp;' . $f['COLUMN_DEFAULT'] . '</td>';
        $html .= '<td class="c_md '.$tdclass.'">&nbsp;' . $f['IS_NULLABLE'] . '</td>';
        $html .= '<td class="c_md '.$tdclass.'">' . ($f['EXTRA']=='auto_increment'?'是':'&nbsp;') . '</td>';
        $html .= '<td class="c_md '.$tdclass.'">' . $f['COLLATION_NAME'] . '</td>';
        $html .= '<td class="c_bg '.$tdclass.'">&nbsp;' . $f['COLUMN_COMMENT'] . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $html .= '</div>';
    $html .= '</p><br/><br/>';
}

//输出
echo '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.$title.'</title>
<style>
//body,td,th {font-family:"微软雅黑"; font-size:12px;}
.table_head {font-size:20px; font-weight:bold;}
.table_body table{border-collapse:collapse;border:1px solid #CCC;background:#efefef;border-collapse:collapse;mso-table-layout-alt:fixed;padding:0.0000pt 5.4000pt 0.0000pt 5.4000pt;}
.table_body table th{text-align:left; font-weight:bold;height:26px; line-height:26px; font-size:12px; border:1px solid #CCC;background:#4f81bd; border-right:1pt solid #4f81bd; border-top:1pt solid #4f81bd; border-bottom:1pt solid #4f81bd;border-left:1pt solid #4f81bd;}
.table_body table th span{color:white; font-weight: bold; font-size:12px; font-family:"微软雅黑"}

.td1{height:20px; font-size:12px; border:1px solid #CCC;background-color:#B8CCE4;border-right:1pt solid #4f81bd; border-top:none; border-bottom:1pt solid #4f81bd;}
.td2{height:20px; font-size:12px; border:1px solid #CCC;background-color:white;border-right:1pt solid #4f81bd; border-top:none; border-bottom:1pt solid #4f81bd;}
.c_sm{ width: 70px;}
.c_md{ width: 120px;}
.c_bg{ width: 270px;}
</style>
</head>
<body>';
echo '<h1>'.$title.'</h1>';
echo $html;
echo '</body></html>';

?>
