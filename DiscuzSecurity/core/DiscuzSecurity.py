import os
import sys
import datetime
import ConfigParser

import core.CDD
import core.util

class DiscuzSecurity:
    def __init__(self, config):
        self.__name__ = 'Discuz Security'

        self.config = ConfigParser.ConfigParser()
        self.config.read(config)

        self.monitor_path = self.config.get('common', 'discuz_path')
        self.cwd = os.getcwd()
        self.today = datetime.datetime.now()
        self.cdd = core.CDD.CDD(self.cwd)

        self.pid_file = '%s/run/ds.pid' % self.cwd
        self.backup_init = '%s/data/backup/init_%s' % (self.cwd, self.today.strftime('%Y-%m-%d'))
        self.scan_log_path = '%s/logs/scan/' % self.cwd
        self.diff_log_path = '%s/logs/diff/' % self.cwd
        self.filter = ['php']
        
        self.is_clean = False
    
    def start_init(self):
        if os.path.lexists(self.pid_file):
            print self.msgstr('piderr')
            sys.exit(1)
        self.init_backup()

    def init_backup(self):
        if os.path.lexists(self.backup_init):
            core.util.move(self.backup_init, self.backup_init + self.today.strftime('_%H_%M_%S'))
        core.util.mkdir(self.backup_init)
        
        phplist = '%s/phplist.log' % self.backup_init
        os.system('find %s -name "*.php" > %s' % (self.monitor_path, phplist))
        files = core.util.read_file(phplist)
        if files:
            for f in files:
                scan = self.scan(f)
                if scan:
                    print 'File %s is dangerous, target rule: %s' % (f, scan)
                    print 'Discuz Security exited!'
                    sys.exit()
                self.backup(f, self.backup_init)

    def event_modify(self, event):
        self.scan(event.pathname, True)
        self.diff(event)

    def event_create(self, event):
        self.backup(event.pathname, self.backup_init)
        self.scan(event.pathname, True)

    def backup(self, f, backup_path):
        os.system('cp --parents %s %s' % (f, backup_path))

    def getcwd(self):
        return self.cwd 

    def scan(self, file_path, is_clean = False):
        result = self.cdd.scan(file_path, True)
        if len(result[0]) > 0:
            self.scan_log(file_path, result[0], 'Danger')
            if is_clean:
                self.clean(file_path)
            return result[0]
        if len(result[1]) > 0:
            self.scan_log(file_path, result[1], 'Warning')
        return None

    def clean(self, file_path):
        backup_path = "%s/%s" % (self.backup_init, file_path)
        if os.path.lexists(backup_path):
            sys.stderr.write('rm %s\n' % file_path)
            sys.stderr.write('mv %s %s\n' % (backup_path, file_path))
            sys.stderr.flush()
            core.util.remove(file_path)
            core.util.copy(backup_path, file_path)

    def diff(self, event):
        file_path = event.pathname
        source_path = "%s/%s" % (self.backup_init, file_path)
        result = self.cdd.diff(source_path, file_path)
        if result:
            self.diff_log(file_path, result)

    def getpid(self):
        if os.path.lexists(self.pid_file):
            f = open(self.pid_file)
            pid = f.read()
            f.close()
            return pid
        else:
            raise IOError
    
    def setpid(self, pid):
        f = open(self.pid_file, 'w')
        f.write(str(pid))
        f.close()

    def msgstr(self, msgno, msgstr=''):
        if msgno == None:
            return '%s: %s' % (self.__name__, msgstr)
        msgstr = {}
        msgstr[1] = '%s: start, stop, restart is needed.'
        msgstr['start'] = '%s: start...'
        msgstr['stop'] = '%s: stop...'
        msgstr['restart'] = '%s: restart...'
        msgstr['clean'] = '%s: clean...'
        msgstr['piderr'] = '%s: PID file already exists.'
        return msgstr[msgno] % self.__name__

    def scan_log(self, file_path, result, level):
        now = datetime.datetime.now()
        log_path = '%s/%s_%s.log' % (self.scan_log_path, now.strftime('%Y_%m_%d'), level)
        for r in result:
            log_message = '%s\t%s\t%s\t%s\n' % (now.strftime('%Y-%m-%d %H:%M:%S'), level, file_path, r) 
            core.util.write_file(log_path, log_message, 'a')

    def diff_log(self, file_path, message):
        now = datetime.datetime.now()
        log_path = '%s/%s.log' % (self.diff_log_path, now.strftime('%Y_%m_%d'))
        log_message = '%s\n%s\n%s\n\n' % (now.strftime('%Y-%m-%d %H:%M:%S'), file_path, message) 
        core.util.write_file(log_path, log_message, 'a')

    def filter_file(self, file):
        l = file.split('.')
        ext = l[len(l)-1]
        if self.filter.count(ext) > 0:
            return file
        else:
            return None
