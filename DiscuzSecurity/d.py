import os,sys
import datetime

import core.DiscuzSecurity
import core.pyinotify
import core.CDD
import core.daemon

class EventHandler(core.pyinotify.ProcessEvent):
    def process_IN_CREATE(self, event):
        filter_file = ds.filter_file(event.name)
        if filter_file:
            ds.event_create(event)
            str = "%s\t%s\t%s\n" % (datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S'), 'IN_CREATE', event.name)
            sys.stdout.write(str)
            sys.stdout.flush()

    def process_IN_MODIFY(self, event):
        filter_file = ds.filter_file(event.name)
        if filter_file:
            ds.event_modify(event)
            str = "%s\t%s\t%s\n" % (datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S' ), 'IN_MODIFY', event.name)
            sys.stdout.write(str)
            sys.stdout.flush()

def start():
    ds.start_init()
    ds_cwd = ds.getcwd()
    core.daemon.daemonize('/dev/null', ds_cwd + '/logs/run.log', ds_cwd + '/logs/error.log')
    ds.setpid(os.getpid())
    wm = core.pyinotify.WatchManager()
    mask = core.pyinotify.IN_CREATE | core.pyinotify.IN_MODIFY
    notifier = core.pyinotify.ThreadedNotifier(wm, EventHandler())
    wm.add_watch(ds.monitor_path, mask, rec=True, auto_add=True)
    notifier.start()

def stop():
    try:
        pid = int(ds.getpid())
    except IOError:
        print "Discuz Security: Can not stop Discuz Security. Because pid file isn't exists."
        exit(1)
    cmd = 'kill %d' % pid
    os.system(cmd)
    os.remove(ds.pid_file)

def clean():
    ds_cwd = ds.getcwd()
    try:
        os.remove(ds.pid_file)
        os.remove(ds_cwd + '/logs/run.log')
        os.remove(ds_cwd + '/logs/error.log')
    except OSError, e:
        print ds.msgstr(None, e.strerror) 

if __name__ == '__main__':

    ds = core.DiscuzSecurity.DiscuzSecurity('data/discuz-security.cnf')

    if len(sys.argv) < 2:
        print ds.msgstr(1)
        exit(0)

    cmd = sys.argv[1]
    if cmd == 'start':
        print ds.msgstr('start') 
        start()
    elif cmd == 'stop':
        print ds.msgstr('stop')
        stop() 
    elif cmd == 'restart':
        print ds.msgstr('restart')
        stop()
        start()
    elif cmd == 'clean':
        print ds.msgstr('clean')
        clean() 
    elif cmd == 'version':
        print core.__name__
        print core.__version__
