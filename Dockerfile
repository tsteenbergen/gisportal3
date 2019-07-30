# Onderstaande regel is nodig voor installatie obv RHEL
# FROM rhel

# Onderstaande regel is nodig voor installatie obv CENTOS
FROM centos

WORKDIR /var/www/html
LABEL maintainer="rob.beffers@rivm.nl"
ENV TZ Europe/Amsterdam

# Instaleren van php 7.2 en Mysql
# Onderstaande regels zijn nodig voor installatie obv CENTOS
RUN yum install -y epel-release
RUN yum install -y http://rpms.remirepo.net/enterprise/remi-release-7.rpm
RUN yum install -y yum-utils
RUN yum-config-manager --enable remi-php72
RUN yum update -y
RUN yum install -y php72 php72-php-fpm php72-php-mysqlnd php72-php-xml php72-php-json
# Onderstaande regels zijn nodig voor installatie obv CENTOS
# ...

# Instaleren van lighttpd
RUN yum install -y lighttpd
RUN yum install -y lighttpd-fastcgi

# lighttpd werkt op poort 8008 (zie ook lighttpd.conf)
EXPOSE 8008

# KOPIEREN VAN FILES
# de files uit de map gisportal moeten naar de root van lighttpd worden gekopieerd
COPY gisportal/. /var/www/html
# de files uit de map etc moeten naarcd ,.. /etc worden gekopieerd
COPY etc/. /etc
COPY etc/fpm.conf /etc/php-fpm.d/www.conf

# Onderstaande regel is nodig voor installatie obv RHEL
# COPY etc/modules.conf /etc/lighttpd/modules.conf

# Het starten van de lighttpd service gebeurt via het umask-geo-mappen.sh script; Deze zet de rechten
# op geo-mappen goed en start vervolgens de lighttpd service.
RUN chmod +x /etc/umask-geo-mappen.sh
CMD /etc/umask-geo-mappen.sh