FROM centos

# RUN mkdir /var/www/html
COPY gisportal/. /var/www/html
WORKDIR /var/www/html

LABEL maintainer="rob.beffers@rivm.nl"
ENV TZ Europe/Amsterdam

# Instaleren van php 7.2 en Mysql
RUN yum install -y epel-release
RUN yum install -y http://rpms.remirepo.net/enterprise/remi-release-7.rpm
RUN yum install -y yum-utils
RUN yum-config-manager --enable remi-php72
RUN yum update -y
RUN yum install -y php72 php72-php-fpm php72-php-mysqlnd php72-php-xml php72-php-json

# Instaleren van lighttpd
RUN yum install -y lighttpd
RUN yum install -y lighttpd-fastcgi

COPY php.ini /etc/php.ini
COPY www.conf /etc/php-fpm.d/www.conf
# COPY modules.conf /etc/lighttpd/modules.conf

# CMD [ "systemctl", "enable", "php-fpm"]
# CMD [ "systemctl", "start", "php-fpm"]

EXPOSE 8008

COPY lighttpd.conf /lighttpd.conf

# CMD while true; do sleep 60; done
# CMD ["lighttpd", "-D", "-f", "/lighttpd.conf"]
COPY umask-geo-mappen.sh /umask-geo-mappen.sh
RUN chmod +x /umask-geo-mappen.sh
CMD /umask-geo-mappen.sh