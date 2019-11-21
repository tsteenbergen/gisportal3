if [ -d "/geo-mappen" ]
then
    umask 0002
    exec lighttpd -D -f /etc/lighttpd.conf
else
    echo "Voeg persistent volume /geo-mappen toe om de build af te kunnen ronden."
    exit 1
fi
