# Testing in a Trellis VM

First, loosen the security settings of MariaDB by doing the following. The MariaDB root password defaults to `devpw`:

    $ sudo mysql -u root

    mysql> USE mysql;
    mysql> UPDATE user SET plugin='mysql_native_password' WHERE User='root';
    mysql> FLUSH PRIVILEGES;
    mysql> exit;

    $ sudo service mysql restart
    
> [Source](https://stackoverflow.com/questions/39281594/error-1698-28000-access-denied-for-user-rootlocalhost)

Next:    

1. `cd /srv/www/pressbooks.test/current/web/app/plugins/pressbooks/pb-cli`
2. `composer install`
3. `export WP_CLI_BIN_DIR="/tmp/wp-cli/"`
4. `bash bin/install-package-tests.sh`
5. `./vendor/bin/behat`

And you’re off to the races.
