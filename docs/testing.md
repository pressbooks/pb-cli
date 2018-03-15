# Testing in a Trellis VM

1. `cd /srv/www/pressbooks.test/current/web/app/plugins/pressbooks/pb-cli`
2. `composer install`
3. `export WP_CLI_BIN_DIR="/tmp/wp-cli/"`
4. `bash bin/install-package-tests.sh`
5. `./vendor/bin/behat`

And you’re off to the races.
