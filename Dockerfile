FROM php:7.2

MAINTAINER Joseph Simmons 'joseph.simmons@forthecity.org'
ENV REFRESHED 2018-12-04
RUN apt-get -y update
RUN apt-get -y dist-upgrade
RUN apt-get install -y wget
RUN wget -O phpunit https://phar.phpunit.de/phpunit-7.phar
RUN chmod +x phpunit
RUN mv phpunit /usr/local/bin/phpunit
VOLUME ['/opt/app']
WORKDIR '/opt/app'
