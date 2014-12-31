Setup on Ubuntu 14.04:

```
sudo apt-get install git php5-cli php5-intl php5-xdebug php5-curl
sudo apt-get install mongodb php5-mongo 

#Download source code
git clone http://gitlab.teltek.es/pumukit/pumukit2.git
cd pumukit2
git checkout develop

#Composer install
curl -sS https://getcomposer.org/installer | php

#Install dependencies
php composer.phar install

#Init mongo db
php app/console doctrine:mongodb:schema:create

#Cache clear
php app/console cache:clear

#Execute tests
php bin/phpunit -c app

#Create the admin user
php app/console fos:user:create admin --super-admin

#Start server
php app/console server:run
```

Check your configuration in  [http://localhost:8000/config.php](http://localhost:8000/config.php)
