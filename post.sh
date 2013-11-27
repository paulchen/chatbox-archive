#!/bin/bash

cd `dirname $0`

cookie_file=`grep cookie_file config.php|sed -e "s/^.*= '//g;s/';//g"`
logfile=`grep logfile config.php|sed -e "s/^.*= '//g;s/';//g"`
tokenfile=`grep tokenfile config.php|sed -e "s/^.*= '//g;s/';//g"`
tmpdir=`grep tmpdir config.php|sed -e "s/^.*= '//g;s/';//g"`
timeout=`grep timeout config.php|sed -e "s/^.*= //g;s/;//g"`

touch $cookie_file

login() {
	rm -f $cookie_file
	rm -f login.html faq.html

	username=`grep forum_user config.php|sed -e "s/^.*= '//g;s/';//g"`
	password=`grep forum_pass config.php|sed -e "s/^.*= '//g;s/';//g"`

	wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies --post-data="vb_login_username=$username&vb_login_password=$password&vb_login_password_hint=Password&cookieuser=1&s=&securitytoken=guest&do=login&vb_login_md5password=&vb_login_md5password_utf=" http://www.informatik-forum.at/login.php?do=login -O login.html -q --timeout=$timeout
	wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies  http://www.informatik-forum.at/faq.php -O faq.html -q --timeout=$timeout
	grep SECURITYTOKEN faq.html|sed -e 's/^.* "//g;s/".*$//' > $tokenfile
	rm login.html faq.html
}

log() {
	echo "`date "+%Y-%m-%d %H:%M:%S"` - $1" >> $logfile
}

if [ "$1" == "" ]; then
	echo "Usage error"
	exit 1
fi

if [ ! -f $tokenfile ]; then
	log "Logging in..."
	login
fi

rm -f $tmpdir/post.xml
log "Posting message: $1"
wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies --post-data="do=cb_postnew&vsacb_newmessage=$1&do=cb_postnew&securitytoken=$token" http://www.informatik-forum.at/misc.php -O $tmpdir/post.xml -q --timeout=$timeout

if [ -s $tmpdir/post.xml ]; then
	rm -f $tmpdir/post.xml
	log "Re-login required... "
	login
	token=`cat $tokenfile`
	log "Posting message: $1"
	wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies --post-data="do=cb_postnew&vsacb_newmessage=$1&do=cb_postnew&securitytoken=$token" http://www.informatik-forum.at/misc.php -O $tmpdir/post.xml -q --timeout=$timeout
	if [ -s $tmpdir/post.xml ]; then
		rm -f $tmpdir/post.xml
		log "Unable to post the message, sorry"
		exit 1
	fi
fi
log "Message successfully posted"
rm -f $tmpdir/post.xml

