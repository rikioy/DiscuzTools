import redis
import datetime
import MySQLdb

class DiscuzRedis:
    _max_num_per_forum = 10000
    _select_forum_sql = "SELECT fid FROM pre_forum_forum WHERE type='forum' AND status = '1'"
    _select_thread_sql = "SELECT tid, replies, dateline, lastpost, views FROM pre_forum_thread WHERE fid = '%d' AND displayorder = '%d' ORDER BY %s LIMIT %d"
    forums =[]
    
    def init(self, mhost, muser, mpw, mdb, mcharset, rhost, rport, rdb):
        self.r = redis.Redis(host=rhost, port=rport, db=rdb)
        self.r.flushall()
        m = MySQLdb.connect(host=mhost, user=muser, passwd=mpw, db=mdb, charset=mcharset)
        self.mcur = m.cursor()
    
    def forum_init(self):
        self.mcur.execute(self._select_forum_sql)
        for f in self.mcur.fetchall():
            self.r.sadd('forums', f[0])
            self.forums.append(f[0])
            
    def thread_init(self):
        for f in self.forums:
            sql = self._select_thread_sql % (f, 0, ' lastpost DESC ', self._max_num_per_forum)
            self.mcur.execute(sql)
            for t in self.mcur.fetchall():
                self.r.zadd(self.zset_name(f, 'replies'), t[0], t[1])
                self.r.zadd(self.zset_name(f, 'dateline'), t[0], t[2])
                self.r.zadd(self.zset_name(f, 'lastpost'), t[0], t[3])
                self.r.zadd(self.zset_name(f, 'views'), t[0], t[4])
                
                
    def zset_name(self, fid, type):
        return "%d-%s" % (fid, type) 

if __name__ == '__main__':
    host = '127.0.0.1'
    dr = DiscuzRedis()
    dr.init(host, 'root', '', 'redis', 'utf8', host, 6379, 0)
    dr.forum_init()
    dr.thread_init()
    