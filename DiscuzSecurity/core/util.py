import os, sys, shutil

def mkdir(d):
    os.mkdir(d)

def copy(old, new):
    shutil.copyfile(old, new)

def move(old, new):
    shutil.move(old, new)

def remove(f):
    os.remove(f)

def read_file(file_path, type = 'line'):
    if not os.path.lexists(file_path):
        return None

    f = open(file_path)
    try:
        try:
            if type == 'line':
                lines = f.read().splitlines()
            elif type == 'all':
                lines = f.read()
        except IOError, e:
            sys.stderr.write("%d: %s\n" % (e.errno, e.strerror))
            lines = None 
    finally:
        f.close()

    return lines

def write_file(file_path, message, mod = 'w'):
    f = open(file_path, mod)
    f.write(message)
    f.close()

def mail(subject, mailto, message_path):
    os.system('mail -s "%s" %s < %s' % (subject, mailto, message_path))

if __name__ == '__main__':
    write_file('/tmp/ttt.log', 'asfsadf', 'a')
