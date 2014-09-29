import os
import datetime
import ConfigParser

import core.util

def report(log_path, subject, to_user_mail, to_discuz_mail):
    if os.path.lexists(log_path):
        core.util.mail(subject, to_discuz_mail, log_path)
        if to_user_mail:
            core.util.mail(subject, to_user_mail, log_path)

def main():
    config = ConfigParser.ConfigParser()
    config.read('data/discuz-security.cnf')
    
    to_user_mail = config.get('mail', 'to_user_mail')
    to_discuz_mail = config.get('mail', 'to_discuz_mail')
    
    now = datetime.datetime.now()
    report_day = now - datetime.timedelta(days=1)

    log_day_str = report_day.strftime("%Y_%m_%d")
    sub_day_str = report_day.strftime("%Y-%m-%d")

    cwd = os.getcwd()

    diff_log = '%s/logs/diff/%s.log' % (cwd, log_day_str)
    report(diff_log, 'diff log on %s' % sub_day_str, to_user_mail, to_discuz_mail)

    danger_log = '%s/logs/scan/%s_Danger.log' % (cwd, log_day_str) 
    report(danger_log, 'danger log on %s' % sub_day_str, to_user_mail, to_discuz_mail)

    warning_log = '%s/logs/scan/%s_Warning.log' % (cwd, log_day_str)
    report(warning_log, 'warning log on %s' % sub_day_str, to_user_mail, to_discuz_mail)

if __name__ == "__main__":
    main()
