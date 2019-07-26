FROM centos

# FROM rhel-minimal

# RUN mkdir /var/www/html
COPY gisportal/. /var/www/html
WORKDIR /var/www/html


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
RUN yum install -y php
RUN yum -y install php-fpm lighttpd-fastcgi

COPY php.ini /etc/php.ini
COPY www.conf /etc/php-fpm.d/www.conf
COPY modules.conf /etc/lighttpd/modules.conf

CMD [ "php", "./index.php" ]
CMD [ "systemctl", "start", "php-fpm"]
CMD [ "systemctl", "enable", "php-fpm"]

EXPOSE 8008

COPY lighttpd.conf /lighttpd.conf

# CMD while true; do sleep 60; done
CMD ["lighttpd", "-D", "-f", "/lighttpd.conf"]
