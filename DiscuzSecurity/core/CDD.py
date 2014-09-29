import os, sys
import re

import core.util

class CDD:
    def __init__(self, cwd):
        self.cwd = cwd
        self.rule_path = '%s/data/rule/rule.dat' % self.cwd
        self.rulext_path = '%s/data/rule/rulext.dat' % self.cwd
        self.whitelist_path = '%s/data/whitelist.dat' % self.cwd

        self.init_rules()
        self.init_whitelist()
    
    def init_rules(self, ext=None):
        self.rules= self.import_rule(self.rule_path)
        if not self.rules:
           sys.stderr.write('import rules error.\n')
           sys.exit(1)
        
        self.rulext = self.import_rule(self.rulext_path)

    def init_whitelist(self):
        self.whitelist = core.util.read_file(self.whitelist_path) 

    def import_rule(self, path):
        lines = core.util.read_file(path)
        if lines:
            new = []
            for l in lines:
                new.append(l.split('<==>'))
            return new
        else:
            return None
    
    def white_list(self, path):
        if not self.whitelist:
            return False
        cwd_path = path[:path.rindex("/")]
        if self.whitelist.count(cwd_path) > 0:
            return True
        if self.whitelist.count(path) > 0:
            return True

    def scan(self, path, rulext = False):
        t_rule = []
        t_rulext = []

        if self.white_list(path):
            return (t_rule, t_rulext)

        content = core.util.read_file(path, 'all')
        for rule in self.rules:
            if len(rule) > 2:
                p1 = re.compile(rule[0], re.I)
                m1 = p1.findall(content)
                if m1:
                    for m in m1:
                        p2 = rule[1].replace('\\1', m)
                        p2 = re.compile(p2, re.I)
                        m2 = p2.search(content)
                        if m2:
                            t_rule.append(m)
            else:
                pattern = re.compile(rule[0], re.I)
                match = pattern.findall(content)
                if match:
                    for m in match:
                        t_rule.append(m) 
        if rulext:
            for rule in self.rulext:
                pattern = re.compile(rule[0], re.I)
                match = pattern.findall(content)
                if match:
                    for m in match:
                        t_rulext.append(m)   
        return (t_rule, t_rulext)

    def diff(self, source, target):
        if self.white_list(target):
            return None
        result = os.popen('diff %s %s' % (source, target)).read()
        return result
