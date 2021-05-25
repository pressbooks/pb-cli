<?php

namespace Pressbooks_CLI\Tests\Context;

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode,
    WP_CLI\Process;

/**
 * Features context.
 */
class FeatureContext extends \WP_CLI\Tests\Context\FeatureContext {
	/**
     * @Given /^a Pressbooks (subdirectory|subdomain) install$/
     */
    public function given_a_pressbooks_install( $subdirectory_or_subdomain = 'subdirectory' ) {
		$this->install_wp();
		$subdomains = ! empty( $subdirectory_or_subdomain ) && 'subdomain' === $subdirectory_or_subdomain ? 1 : 0;
		$this->proc( 'wp core install-network', array( 'title' => 'WP CLI Network', 'subdomains' => $subdomains ) )->run_check();
		$this->proc( 'wp plugin install https://github.com/pressbooks/pressbooks/archive/dev.zip' )->run_check();
		$this->proc( 'wp theme install https://github.com/pressbooks/pressbooks-book/archive/dev.zip' )->run_check();
		$this->proc( 'wp theme install https://github.com/pressbooks/pressbooks-aldine/archive/dev.zip' )->run_check();
		$this->proc( 'cd wp-content/plugins/pressbooks && composer install --no-dev --optimize-autoloader && cd ../../../' )->run_check();
		$this->proc( 'cd wp-content/themes/pressbooks-aldine && composer install --no-dev --optimize-autoloader && cd ../../../' )->run_check();
		$this->proc( 'cd wp-content/themes/pressbooks-book && composer install --no-dev --optimize-autoloader && cd ../../../' )->run_check();
		@mkdir( 'wp-content/mu-plugins' );
		$this->move_files( 'wp-content/plugins/pressbooks/hm-autoloader.php', 'wp-content/mu-plugins/hm-autoloader.php' );
		$this->proc( 'wp plugin activate pressbooks --network' )->run_check();
		$this->proc( 'wp theme enable pressbooks-book --network' )->run_check();
		$this->proc( 'wp theme activate pressbooks-aldine' )->run_check();
		$this->proc( 'wp site create --slug=standardtest --title="Standard Test Book"' )->run_check();
	}
}
