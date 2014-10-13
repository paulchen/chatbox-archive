#!/bin/bash

logfile=archive.log

log() {
	echo "`date "+%Y-%m-%d %H:%M:%S"` - $1" >> $logfile
}

page=$1
while true; do
	page=$((page+1))
	log "Fetching page $page... "
	rm -f abc$page.xml
	wget --load-cookies ../tmp/cookies.txt --save-cookies ../tmp/cookies.txt --keep-session-cookies http://signanz.htu.tuwien.ac.at/misc.php?do=ccarc\&page=$page1 --timeout=60 --header="Host: www.informatik-forum.at" -q -O archive/archive1-$page.xml
	log "processing... "
	iconv -f iso-8859-1 -t utf-8 archive/archive1-$page.xml -o archive/archive2-$page.xml
	# rm abc$page.xml
	dos2unix archive/archive2-$page.xml > /dev/null 2>&1
	php ../extract_shouts.php archive/archive2-$page.xml
	count=`echo $?`
	# rm def$page.xml
	log "$count shout(s) saved"
done

