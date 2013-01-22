#!/bin/bash

cd `dirname $0`

cookie_file=`grep cookie_file config.php|sed -e "s/^.*= '//g;s/';//g"`
lockfile=`grep lockfile config.php|sed -e "s/^.*= '//g;s/';//g"`
logfile=`grep logfile config.php|sed -e "s/^.*= '//g;s/';//g"`

exec 9>$lockfile
if ! flock -n 9  ; then
	exit
fi

touch $cookie_file

login() {
	rm -f $cookie_file
	rm -f login.html faq.html

	username=`grep forum_user config.php|sed -e "s/^.*= '//g;s/';//g"`
	password=`grep forum_pass config.php|sed -e "s/^.*= '//g;s/';//g"`

	wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies --post-data="vb_login_username=$username&vb_login_password=$password&vb_login_password_hint=Password&cookieuser=1&s=&securitytoken=guest&do=login&vb_login_md5password=&vb_login_md5password_utf=" http://www.informatik-forum.at/login.php?do=login -O login.html -q
	wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies  http://www.informatik-forum.at/faq.php -O faq.html -q
	grep SECURITYTOKEN faq.html|sed -e 's/^.* "//g;s/".*$//' > securitytoken
	rm login.html faq.html
}

log() {
	echo "`date "+%Y-%m-%d %H:%M:%S"` - $1" >> $logfile
}

if [ ! -f securitytoken ]; then
	log "Logging in..."
	login
fi

while true; do
	token=`cat securitytoken`
	log "Fetching chatbox... "
	rm -f cb1.xml
	wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies --post-data="undefined&securitytoken=$token&s=" http://www.informatik-forum.at/misc.php?show=ccbmessages -O cb1.xml -q
	if [ `grep -c "DOCTYPE" cb1.xml` -ne 0 ]; then
		rm cb1.xml
		log "Re-login required... "
		login
		token=`cat securitytoken`
		log "Fetching chatbox..."
		wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies --post-data="undefined&securitytoken=$token&s=" http://www.informatik-forum.at/misc.php?show=ccbmessages -O cb1.xml -q
		if [ `grep -c "DOCTYPE" cb1.xml` -ne 0 ]; then
			log "Unable to fetch chatbox contents, terminating now."
			exit 1
		fi
	fi

	log "Processing... "
	rm -f cb2.xml
	iconv -f iso-8859-1 -t utf-8 cb1.xml -o cb2.xml
	rm cb1.xml
	dos2unix cb2.xml > /dev/null 2>&1
	php extract_shouts.php cb2.xml
	count=`echo $?`
	rm cb2.xml
	log "$count shout(s) saved"
	if [ "$count" -eq 30 ]; then
		page=1
		while true; do
			page=$((page+1))
			log "Fetching archive page $page... "
			rm -f abc$page.xml
			wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies http://www.informatik-forum.at/misc.php?do=ccarc\&page=$page -q -O abc$page.xml
			log "Processing... "
			rm -f def$page.xml
			iconv -f iso-8859-1 -t utf-8 abc$page.xml -o def$page.xml
			rm abc$page.xml
			dos2unix def$page.xml > /dev/null 2>&1
			php extract_shouts.php def$page.xml
			count=`echo $?`
			rm def$page.xml
			log "$count shout(s) saved"
			if [ "$count" -eq 0 ]; then
				log "Done."
				break
			fi
		done
	else 
		log "Done."
	fi

	log "Waiting 10 seconds... "
	sleep 10
done

