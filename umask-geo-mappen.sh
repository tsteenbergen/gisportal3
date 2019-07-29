if [ -d "/geo-mappen" ]
then
    umask 0002
    if [ -d "/geo-mappen/geo-packages" ]
	then
		mkdir "/geo-mappen/geo-packages"
	fi
    exec lighttpd -D -f /lighttpd.conf
else
    echo "Data directory (/geo-mappen) does not exist!"
    exit 1
fi
