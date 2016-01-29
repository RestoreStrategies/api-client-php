FROM php:5

MAINTAINER Joseph Simmons 'joseph@austinstone.org'
ENV REFRESHED 2016-01-26
RUN apt-get -y update
RUN apt-get -y dist-upgrade
RUN apt-get install -y wget
RUN wget https://phar.phpunit.de/phpunit.phar
RUN chmod +x phpunit.phar
RUN mv phpunit.phar /usr/local/bin/phpunit
VOLUME ['/opt/app']
WORKDIR '/opt/app'
