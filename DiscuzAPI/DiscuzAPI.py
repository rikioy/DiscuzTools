#! /usr/bin/env python
# -*- coding: utf-8 -*-

"""
base by Conanca
image upload by N3il
"""

import urllib2
import urllib
import cookielib
import random
import string
import re
import time
import sys
import httplib
import mimetools
import mimetypes

httplib.HTTPConnection.debuglevel = 1

class DiscuzAPI:
	def __init__(self, forumUrl, userName, password, proxy = None):
		''' 初始化论坛url、用户名、密码和代理服务器 '''
		self.forumUrl = forumUrl
		self.userName = userName
		self.password = password
		self.formhash = ''
		self.isLogon = False
		self.isSign = False
		self.xq = ''
		self.jar = cookielib.CookieJar()
		if not proxy:
			openner = urllib2.build_opener(urllib2.HTTPCookieProcessor(self.jar))
		else:
			openner = urllib2.build_opener(urllib2.HTTPCookieProcessor(self.jar), urllib2.ProxyHandler({'http' : proxy}))
		urllib2.install_opener(openner)
 
	def login(self):
		''' 登录论坛 '''
		url = self.forumUrl + "/member.php?mod=logging&action=login&loginsubmit=yes&infloat=yes&inajax=1";
		postData = urllib.urlencode({'username': self.userName, 'password': self.password, 'answer': '', 'cookietime': '2592000', 'handlekey': 'ls', 'questionid': '0', 'quickforward': 'yes',  'fastloginfield': 'username'})
		req = urllib2.Request(url,postData)
		content = urllib2.urlopen(req).read()
		if self.userName.encode('gbk') in content:
			self.isLogon = True
			print 'logon success!'
			self.initFormhashXq()
			return 1
		else:
			print 'logon faild!'
			return 0
 
	def initFormhashXq(self):
		''' 获取formhash和心情 '''
		content = urllib2.urlopen(self.forumUrl + '/plugin.php?id=dsu_paulsign:sign').read().decode('gbk')
		rows = re.findall(r'<input type=\"hidden\" name=\"formhash\" value=\"(.*?)\" />', content)
		if len(rows)!=0:
			self.formhash = rows[0]
			print 'formhash is: ' + self.formhash
		else:
			print 'none formhash!'
		rows = re.findall(r'<input id=.* type=\"radio\" name=\"qdxq\" value=\"(.*?)\" style=\"display:none\">', content)
		if len(rows)!=0:
			self.xq = rows[0]
			print 'xq is: ' + self.xq
		elif u'已经签到' in content:
			self.isSign = True
			print 'signed before!'
		else:
			print 'none xq!'
 
	def reply(self, tid, subject = u'',msg = u'支持~~~顶一下下~~嘻嘻'):
		''' 回帖 '''
		url = self.forumUrl + '/forum.php?mod=post&action=reply&fid=41&tid='+str(tid)+'&extra=page%3D1&replysubmit=yes&infloat=yes&handlekey=fastpost&inajax=1'
		postData = urllib.urlencode({'formhash': self.formhash, 'message': msg.encode('gbk'), 'subject': subject.encode('gbk'), 'posttime':int(time.time()) })
		req = urllib2.Request(url,postData)
		content = urllib2.urlopen(req).read().decode('gbk')
		#print content
		if u'发布成功' in content:
			print 'reply success!'
		else:
			print 'reply faild!'
 
	def publish(self, fid, typeid, subject = u'发个帖子测试一下下，嘻嘻~~~',msg = u'发个帖子测试一下下，嘻嘻~~~', imgId = ""):
		''' 发帖 '''
		url = self.forumUrl + '/forum.php?mod=post&action=newthread&fid='+ str(fid) +'&extra=&topicsubmit=yes'
		"""
		formhash=d649673a&posttime=1367460177&wysiwyg=1&subject=test&unused%5B%5D=70554
		&message=tset123214141&save=&attachnew%5B70555%5D%5Bdescription%5D=&usesig=1&allownoticeauthor=1
		"""
		postData = urllib.urlencode(
					{'formhash': self.formhash, 
					'message': msg.encode('gbk'),
					'subject': subject.encode('gbk'),
					'posttime':int(time.time()),
					'addfeed':'1', 
					'allownoticeauthor':'1', 
					'checkbox':'0', 
					'newalbum':'', 
					'readperm':'', 
					'rewardfloor':'', 
					'rushreplyfrom':'', 
					'rushreplyto':'', 
					'save':'', 
					'stopfloor':'', 
					#'typeid':typeid,
					'attachnew[%s][description]' % imgId: "",
					'uploadalbum':'', 
					'usesig':'1', 
					'wysiwyg':'0' })
		req = urllib2.Request(url,postData)
		content = urllib2.urlopen(req).read().decode('gbk')
		#print content
		if u"您的主题已发布" in content:
			print 'publish success!'
			return 1
		else:
			print 'publish faild!'
			return 0
 
	def sign(self,msg = u'哈哈，我来签到了！'):
		''' 签到 '''
		if self.isSign:
			return
		if self.isLogon and self.xq:
			url = self.forumUrl + '/plugin.php?id=dsu_paulsign:sign&operation=qiandao&infloat=1&inajax=1'
			postData = urllib.urlencode({'fastreply': '1', 'formhash': self.formhash, 'qdmode': '1', 'qdxq': self.xq, 'todaysay':msg.encode('gbk') })
			req = urllib2.Request(url,postData)
			content = urllib2.urlopen(req).read().decode('gbk')
			#print content
			if u'签到成功' in content:
				self.isSign = True
				print 'sign success!'
				return
		print 'sign faild!'
 
	def speak(self,msg = u'hah,哈哈，测试一下！'):
		''' 发表心情 '''
		url = self.forumUrl + '/home.php?mod=spacecp&ac=doing&handlekey=doing&inajax=1'
		postData = urllib.urlencode({'addsubmit': '1', 'formhash': self.formhash, 'referer': 'home.php', 'spacenote': 'true', 'message':msg.encode('gbk') })
		req = urllib2.Request(url,postData)
		content = urllib2.urlopen(req).read().encode('gbk')
		#print content
		if u'操作成功' in content:
			print 'speak success!'
		else:
			print 'speak faild!'

	def uploadImage(self, imageData, fid=21):
		imageId = None
		# get the uid and hash
		url = self.forumUrl + "/forum.php?mod=post&action=newthread&fid=%d&extra=" % fid
		data = urllib2.urlopen(url).read().decode('gbk')
		hashReg = re.compile(r"<input type=\"hidden\" name=\"hash\" value=\"(.*?)\">", re.S)
		uidReg = re.compile(r"discuz_uid = '(.*?)'", re.S)
		hashRet = hashReg.search( data ).group(1)
		uid = uidReg.search( data ).group(1)

		# Upload the image
		uploadImageUrl = self.forumUrl + "/misc.php?mod=swfupload&operation=upload&simple=1&type=image"
		refer = self.forumUrl + "/forum.php?mod=post&action=newthread&fid=%d&extra=" % fid
		randomStr = "7dd" + ''.join( random.sample(string.ascii_lowercase + string.digits, 8) )
		CRLF = '\r\n'
		#BOUNDARY = mimetools.choose_boundary()
		BOUNDARY = "---------------------------" + randomStr
		L = []
		L.append('--' + BOUNDARY)
		L.append("Content-Disposition: form-data; name=\"uid\""  )
		L.append("")
		L.append(uid)
		L.append('--' + BOUNDARY)
		L.append('Content-Disposition: form-data; name=\"hash\"')
		L.append("")
		L.append(hashRet)
		L.append('--' + BOUNDARY)
		L.append('Content-Disposition: form-data; name=\"Filedata\"; filename=\"testpic.jpg\"')
		L.append("Content-Type: image/pjpeg")
		L.append("")
		L.append( imageData )
		L.append('--' + BOUNDARY + '--')
		L.append("")
		postData = CRLF.join(str(a) for a in L)

		#print postData

		req = urllib2.Request(uploadImageUrl, postData) 
		req.add_header('Content-Type', 'multipart/form-data; boundary=%s' % BOUNDARY )
		req.add_header('Content-Length',  len(postData) )
		req.add_header('Referer', refer )
		resp = urllib2.urlopen(req)
		body = resp.read().decode('utf-8')
		bodySp = body.split('|')
		if len(bodySp) == 0:
			return None
		if bodySp[0] == u'DISCUZUPLOAD' and bodySp[1] == u'0':
			imageId = bodySp[2]
		return imageId
 
if __name__ == '__main__':
	robot = DiscuzAPI(sys.argv[1], sys.argv[2], sys.argv[3])
	robot.login()
	robot.sign()
	#robot.speak()
	#robot.publish(21,36)
	#robot.reply(10)
	imageData = open('test.jpg', 'rb').read()
	print robot.uploadImage( imageData )

