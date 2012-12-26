#!/bin/bash
page=$1
while true; do
	page=$((page+1))
	date
	echo -n "Fetching page $page... "
	rm -f abc$page.xml
	wget --load-cookies cookies.txt --save-cookies cookies.txt --keep-session-cookies http://www.informatik-forum.at/misc.php?do=ccarc\&page=$page -q -O abc$page.xml
	echo -n "processing... "
	iconv -f iso-8859-1 -t utf-8 abc$page.xml -o def$page.xml
	rm abc$page.xml
	dos2unix def$page.xml > /dev/null 2>&1
	php extract_shouts.php def$page.xml
	count=`echo $?`
	rm def$page.xml
	echo "$count shout(s) saved"
done

