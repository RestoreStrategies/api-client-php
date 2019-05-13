FROM php:5.4

MAINTAINER Joseph Simmons 'joseph.simmons@forthecity.org'
ENV REFRESHED 2019-05-13
RUN printf "deb http://archive.debian.org/debian/ jessie main\ndeb-src http://archive.debian.org/debian/ jessie main\ndeb http://security.debian.org jessie/updates main\ndeb-src http://security.debian.org jessie/updates main" > /etc/apt/sources.list
RUN apt-get -y update
RUN apt-get -y dist-upgrade
RUN apt-get install -y wget
RUN wget -O phpunit https://phar.phpunit.de/phpunit-4.phar
RUN chmod +x phpunit
RUN mv phpunit /usr/local/bin/phpunit
VOLUME ['/opt/app']
WORKDIR '/opt/app'
