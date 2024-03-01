Toolforge Admin
===============

Wikimedia Toolforge landing page and general information.


Installing a development environment
------------------------------------

You'll need to have a redis service accessible, you can run one with:
```
podman run --name admin-web-redis --publish 6379:6379 --detach redis:latest
```

A mariadb service:
```
podman run --publish 3306:3306  --name admin-web-mariadb --env MARIADB_ROOT_PASSWORD=my-secret-pw  --detach mariadb:latest
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

If you see an error like:
```
[Wed Sep 23 11:25:16 2026] PHP Fatal error:  Uncaught ErrorException: Return type of Slim\Environment::offsetExists($offset) should either be compatible with ArrayAccess::offsetExists(mixed $offset): bool, or the #[\ReturnTypeWillChange] attribute should be used to temporarily suppress the notice in /Users/fran/wmf/toolforge/admin-web/vendor/slim/slim/Slim/Environment.php:186
```

You might need to pass an extra `-d error_reporting="E_ALL & ~E_DEPRECATED"` to the previous `php` command line.

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
