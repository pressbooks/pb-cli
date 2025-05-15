<?php

namespace Pressbooks_CLI;

use WP_CLI;

use Pressbooks\DataCollector\Book;

class PopulateBooksAdminsCommand extends PB_CLI_Command {
    /**
     * Populate book admins.
     *
     * ## OPTIONS
     *
     * @when after_wp_load
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function populate( $args, $assoc_args ) {

        if ( ! is_multisite() ) {
            return;
        }

        // populate pb_book_admins blogmeta for all books using updateAllBooksAdmins function
        $bookCollector = new Book();
        $bookCollector->updateAllBooksAdmins();

        WP_CLI::success( 'Book admins populated successfully.' );

    }
}