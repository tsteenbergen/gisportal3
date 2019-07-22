FROM centos

LABEL maintainer="rob.beffers@rivm.nl"
ENV TZ Europe/Amsterdam

# Voor lokale ontwikkeling moet je de repo opgeven...
# subscription-manager repos --enable=rhel-7-server-supplementary-rpms
# RUN yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm

# RUN yum install -y httpd
# COPY gisportal/index.html /var/www/html
# CMD ["/usr/sbin/httpd", "-D", "FOREGROUND"]
# EXPOSE 80
# CMD while true; do sleep 60; done

RUN yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
RUN yum install -y lighttpd

RUN mkdir /var/www/html
COPY gisportal/index.html /var/www/html

EXPOSE 8008

COPY lighttpd.conf /lighttpd.conf

CMD ["lighttpd", "-D", "-f", "/lighttpd.conf"]