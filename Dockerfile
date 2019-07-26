FROM centos
# FROM rhel-minimal

# RUN mkdir /var/www/html
COPY gisportal/. /var/www/html
WORKDIR /var/www/html

LABEL maintainer="rob.beffers@rivm.nl"
ENV TZ Europe/Amsterdam

RUN yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
RUN yum install -y lighttpd
RUN yum install -y php
RUN yum -y install php-fpm lighttpd-fastcgi

# COPY php.ini /etc/php.ini
# COPY www.conf /etc/php-fpm.d/www.conf
# COPY modules.conf /etc/lighttpd/modules.conf

# CMD [ "systemctl", "enable", "php-fpm"]
# CMD [ "systemctl", "start", "php-fpm"]

EXPOSE 8008

COPY lighttpd.conf /lighttpd.conf

# CMD while true; do sleep 60; done
CMD ["lighttpd", "-D", "-f", "/lighttpd.conf"]
