Toolforge Admin
===============

Wikimedia Toolforge landing page and general information.


Installing a development environment
------------------------------------

You'll need to have a redis service accessible, you can run one with:
```
podman run --rm --name tools-admin-redis --publish 6379:6379 --rm redis:latest
```

A mariadb service:
```
podman run --publish 3306:3306  --name tools-admin-mariadb --env MARIADB_ROOT_PASSWORD=my-secret-pw  mariadb:latest
```

You'll need to have installed the packages `php8.2`, `php-pdo`, `php-redis`, `composer`, `php-mysql` (`php-mysqlnd` in fedora), then install the deps with composer:
```
composer install
```

Setup the database (only the first time, requires having `mariadb-client` installed):
```
mariadb -h 127.0.0.1 -u root -p < db_schema.sql
```


And start the local server with:
```
env DB_USER=root DB_PASS=my-secret-pw DB_DSN="mysql:host=127.0.0.1;dbname=toollabs_p" REDIS_HOST=127.0.0.1 php -S localhost:8000 -t public/
```

You can add some test data with:
```
mariadb -h 127.0.0.1 -u root -p toollabs_p < db_test_data.sql 
```


License
-------
Toolforge Admin is licensed under the GPL 3.0 license and copyright 2017
Wikimedia Foundation and contributors. See the `LICENSE` file for more
details.

This project began as port of the web application component of
https://phabricator.wikimedia.org/diffusion/LTOL/. The original implementation
was released under the ISC License, copyright 2013 Marc-André Pelletier.
