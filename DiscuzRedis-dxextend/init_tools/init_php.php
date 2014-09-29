<?php
error_reporting(1);

class DiscuzRedis
{
    var $_mysql;
    var $_redis;
    var $_forums;
    var $_select_forum_sql;
    var $_select_thread_sql;
    var $_max_num_per_forum;
    
    function __construct($mhost, $muser, $mpw, $mdb, $rhost, $rport) {
        echo "new discuzredis()\n";
        $this->_mysql = mysql_connect($mhost, $muser, $mpw) or die('Error connect to MySQL server.');
        mysql_select_db($mdb, $this->_mysql);
        $this->_redis = new Redis();
        $this->_redis->connect($rhost, $rport);
    }
    
    function init($_max_num_per_forum) {
        echo "Init...\n";
        $this->_max_num_per_forum =  $_max_num_per_forum;
        $this->_redis->flushall();
    }
    
    function forum_init() {
        echo "Init forums...\n";
        $sql = "SELECT fid FROM pre_forum_forum WHERE type='forum' AND status = '1'";
        $query = mysql_query($sql, $this->_mysql);
        
        $this->_forums = array();
        
        while($f = mysql_fetch_array($query, MYSQL_ASSOC)) {
            $this->_redis->sAdd('forums', $f['fid']);
            $this->_forums[] = $f['fid'];
        }
    }
    
    function thread_init() {
        echo "Init threads...\n";
        foreach($this->_forums as $f) {
            $sql = "SELECT tid, replies, dateline, lastpost, views FROM pre_forum_thread WHERE fid = $f AND displayorder = 0 ORDER BY lastpost DESC LIMIT $this->_max_num_per_forum";
            $query = mysql_query($sql, $this->_mysql);
            while($t = mysql_fetch_array($query, MYSQL_ASSOC)) {
                $this->_redis->zAdd($f.'-replies', $t['replies'], $t['tid']);
                $this->_redis->zAdd($f.'-dateline', $t['dateline'], $t['tid']);
                $this->_redis->zAdd($f.'-lastpost', $t['lastpost'], $t['tid']);
                $this->_redis->zAdd($f.'-views', $t['views'], $t['tid']);
            }
            ///增加一级置顶处理
            $sql = "SELECT tid FROM pre_forum_thread WHERE fid = $f AND displayorder = 1 ORDER BY lastpost DESC";
            $query = mysql_query($sql, $this->_mysql);
            while($t = mysql_fetch_array($query, MYSQL_ASSOC)) {
                $this->_redis->zAdd($f.'-top', $t['dateline'], $t['tid']);
            }
        }
    }
}

$mhost = '127.0.0.1';
$muser = 'root';
$mpw = '';
$mdb = 'x25redis';
$rhost = '127.0.0.1';
$rport = 6379;
$_max_num_per_forum = 100000;

$dr = new DiscuzRedis($mhost, $muser, $mpw, $mdb, $rhost, $rport);
$dr->init($_max_num_per_forum);
$dr->forum_init();
$dr->thread_init();