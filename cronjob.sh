#!/bin/bash

cookie_file=cookies2.txt

touch $cookie_file

login() {
	rm -f $cookie_file
	wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies --post-data='vb_login_username=signanz&vb_login_password=4711pst4711&vb_login_password_hint=Password&cookieuser=1&s=&securitytoken=guest&do=login&vb_login_md5password=&vb_login_md5password_utf=' http://www.informatik-forum.at/login.php?do=login -O login.html -q
	grep SECURITYTOKEN login.html|sed -e 's/^.* "//g;s/".*$//' > securitytoken
	rm login.html
}

cd `dirname $0`
if [ ! -f securitytoken ]; then
	login
fi

while true; do
	token=`cat securitytoken`
	date
	echo -n "Fetching chatbox... "
	wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies --post-data="undefined&securitytoken=$token&s=" http://www.informatik-forum.at/misc.php?show=ccbmessages -O cb1.xml -q
	if [ `grep -c "first visit" cb1.xml` -ne 0 ]; then
		rm cb1.xml
		echo "re-login required... "
		login
		token=`cat securitytoken`
		date
		echo -n "Fetching chatbox..."
		wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies --post-data="undefined&securitytoken=$token&s=" http://www.informatik-forum.at/misc.php?show=ccbmessages -O cb1.xml -q
		if [ `grep -c "first visit" cb1.xml` -ne 0 ]; then
			echo "unable."
			exit 1
		fi
	fi

	echo -n "processing... "
	iconv -f iso-8859-1 -t utf-8 cb1.xml -o cb2.xml
	rm cb1.xml
	dos2unix cb2.xml > /dev/null 2>&1
	php extract_shouts.php cb2.xml
	count=`echo $?`
	#rm cb2.xml
	echo "$count shout(s) saved"
	if [ "$count" -eq 30 ]; then
		page=1
		while true; do
			page=$((page+1))
			date
			echo -n "Fetching page $page... "
			rm -f abc$page.xml
			wget --load-cookies $cookie_file --save-cookies $cookie_file --keep-session-cookies http://www.informatik-forum.at/misc.php?do=ccarc\&page=$page -q -O abc$page.xml
			echo -n "processing... "
			iconv -f iso-8859-1 -t utf-8 abc$page.xml -o def$page.xml
			rm abc$page.xml
			dos2unix def$page.xml > /dev/null 2>&1
			php extract_shouts.php def$page.xml
			count=`echo $?`
			rm def$page.xml
			echo "$count shout(s) saved"
			if [ "$count" -eq 0 ]; then
				echo "Done."
				break
			fi
		done
	else 
		echo "Done."
	fi

	date
	echo -n "Waiting 10 seconds... "
	sleep 10
	echo "done."
done

