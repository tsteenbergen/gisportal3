# ******************  Onderstaande regel is nodig voor installatie obv Centos ********************
FROM centos
# ************************************************************************************************
# ******************  Onderstaande regel is nodig voor installatie obv RHEL **********************
# FROM rhel
# ************************************************************************************************

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
# Onderstaande regels zijn nodig voor installatie obv RHEL
# ...

# TBV ontwikkeling GIT installeren
RUN yum install -y git

# Instaleren van lighttpd
RUN yum install -y lighttpd
RUN yum install -y lighttpd-fastcgi

# lighttpd werkt op poort 8080 (zie ook lighttpd.conf)
EXPOSE 8080

# KOPIEREN VAN FILES
# de files uit de map gisportal moeten naar de root van lighttpd worden gekopieerd
RUN mkdir -p /var/www/html/geo/portal
COPY gisportal/. /var/www/html/geo/portal
# de files uit de map etc moeten naar verschillende mappen worden gekopieerd (soms met andere naam!)
COPY etc/umask-geo-mappen.sh /etc/umask-geo-mappen.sh
COPY etc/lighttpd.conf /etc/lighttpd.conf
COPY etc/php.ini /etc/opt/remi/php72/php.ini
COPY etc/fpm.conf /etc/php-fpm.d/www.conf

# ******************  Onderstaande regels zijn nodig voor installatie obv RHEL *******************
# COPY etc/modules.conf /etc/lighttpd/modules.conf
# ************************************************************************************************

# Het starten van de lighttpd service gebeurt via het umask-geo-mappen.sh script; Deze zet de rechten
# op geo-mappen goed en start vervolgens de lighttpd service.
RUN chmod +x /etc/umask-geo-mappen.sh
CMD /etc/umask-geo-mappen.sh
