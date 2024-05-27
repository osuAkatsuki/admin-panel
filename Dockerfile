FROM ubuntu:22.04

WORKDIR /usr/src/app

COPY . .
COPY scripts /scripts

RUN apt update && apt install -y software-properties-common wget
RUN add-apt-repository -y ppa:ondrej/php && apt update

RUN apt install -y \
    php7.2-cli \
    php7.2-common \
    php7.2-fpm \
    php7.2-bcmath \
    php7.2-bz2 \
    php7.2-curl \
    php7.2-dom \
    php7.2-gd \
    php7.2-iconv \
    php7.2-intl \
    php7.2-json \
    php7.2-mbstring \
    php7.2-mysql \
    php7.2-opcache \
    php7.2-pdo \
    php7.2-soap \
    php7.2-sqlite3 \
    php7.2-xml \
    php7.2-xmlrpc \
    php7.2-zip \
    php7.2-redis \
    nginx

RUN update-alternatives --set php /usr/bin/php7.2

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
   && php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
   && php composer-setup.php \
   && php -r "unlink('composer-setup.php');" \
   && mv composer.phar /usr/local/bin/composer

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install

COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/fastcgi.conf /etc/nginx/fastcgi.conf
COPY nginx/sites-enabled/ /etc/nginx/sites-enabled/

RUN wget https://bootstrap.pypa.io/get-pip.py \
    && python3.10 get-pip.py \
    && pip install git+https://github.com/osuAkatsuki/akatsuki-cli \
    && rm get-pip.py

ENTRYPOINT [ "/scripts/bootstrap.sh" ]
