FROM centos

LABEL maintainer="rob.beffers@rivm.nl"
ENV TZ Europe/Amsterdam

# Voor lokale ontwikkeling moet je de repo opgeven...
# subscription-manager repos --enable=rhel-7-server-supplementary-rpms

# RUN yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm

RUN yum install -y httpd
RUN systemctl start httpd.service
# CMD while true; do sleep 60; done