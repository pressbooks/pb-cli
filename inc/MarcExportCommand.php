<?php
/**
 * Minimal MARCXML export over WP-CLI.
 *
 * Exports the first 10 public blogs (excluding the network main site) as MARCXML
 * to STDOUT. Intended as a starting point; extend with options later.
 */

namespace Pressbooks_CLI;

use Pressbooks\Book;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
    return;
}

class MarcExportCommand {
    public static function register(): void {
        WP_CLI::add_command( 'pb marc-export', [ static::class, 'handle' ] );
    }

    /**
     * Export first 10 public blogs (excluding main site) to MARCXML on STDOUT.
     *
     * ## EXAMPLES
     *
     *     wp pressbooks marc-export > /tmp/pressbooks-marc.xml
     */
    public static function handle(): void {
        global $wpdb;

        $site_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs} WHERE deleted=0 AND blog_id=113 AND archived=0 ORDER BY blog_id LIMIT 10" );
        if ( empty( $site_ids ) ) {
            WP_CLI::warning( 'No public sites found.' );
            echo self::empty_collection();
            return;
        }

        $writer = new \XMLWriter();
        $writer->openURI( 'php://output' );
        $writer->startDocument( '1.0', 'UTF-8' );
        $writer->startElement( 'collection' );
        $writer->writeAttribute( 'xmlns', 'http://www.loc.gov/MARC21/slim' );

        $count = 0;
        foreach ( $site_ids as $blog_id ) {
            if ( is_main_site( (int) $blog_id ) ) {
                continue;
            }
            // Stop after first 10 eligible sites
            if ( $count >= 10 ) {
                break;
            }

            // Gather metadata
            $info = Book::getBookInformation( (int) $blog_id, false );
            $title = isset( $info['pb_title'] ) ? self::clean( $info['pb_title'] ) : '';
            if ( $title === '' ) {
                continue; // Skip empty title
            }

            $details = get_blog_details( (int) $blog_id );
            $url = $details && ! empty( $details->siteurl ) ? $details->siteurl : '';

            self::write_record( $writer, (int) $blog_id, $info, $title, $url );
            $count++;
        }

        $writer->endElement(); // collection
        $writer->endDocument();
        $writer->flush();
    }

    private static function write_record( \XMLWriter $writer, int $blog_id, array $info, string $title, string $url ): void {
        $writer->startElement( 'record' );

        // Leader (simple monograph)
        $writer->startElement( 'leader' );
        $writer->text( '00000nam a2200000 i 4500' );
        $writer->endElement();

        // 001 Control number
        self::controlfield( $writer, '001', (string) $blog_id );
        // 003 Organization code (placeholder PB)
        self::controlfield( $writer, '003', 'PB' );
        // 005 Last modified (now, UTC)
        self::controlfield( $writer, '005', gmdate( 'YmdHis' ) . '.0Z' );
        // 008 Minimal fixed-length data
        $year = self::year( $info['pb_pub_date'] ?? '' );
        $lang = self::marc_lang( $info['pb_language'] ?? '' );
        $today = gmdate( 'ymd' );
        $fixed = sprintf( '%s%s%s%s',
            $today,               // 00-05 date entered
            's',                  // 06 type of date (single)
            $year ?: 'uuuu',      // 07-10 year
            str_repeat( ' ', 24 ) // pad to 35
        );
        $fixed = str_pad( $fixed, 35, ' ' ) . ( $lang ?: 'und' ) . str_repeat( ' ', 40 - 38 );
        self::controlfield( $writer, '008', $fixed );

        // 245 Title
        $writer->startElement( 'datafield' );
        $writer->writeAttribute( 'tag', '245' );
        $writer->writeAttribute( 'ind1', '1' );
        $writer->writeAttribute( 'ind2', '0' );
        self::subfield( $writer, 'a', $title );
        $writer->endElement();

        // 520 Summary (short description if present)
        $summary = '';
        if ( ! empty( $info['pb_about_50'] ) ) {
            $summary = self::plain_text( $info['pb_about_50'] );
        } elseif ( ! empty( $info['pb_about_unlimited'] ) ) {
            $summary = self::plain_text( $info['pb_about_unlimited'] );
        }
        if ( $summary !== '' ) {
            $writer->startElement( 'datafield' );
            $writer->writeAttribute( 'tag', '520' );
            $writer->writeAttribute( 'ind1', ' ' );
            $writer->writeAttribute( 'ind2', ' ' );
            self::subfield( $writer, 'a', $summary );
            $writer->endElement();
        }

        // 856 URL
        if ( $url ) {
            $writer->startElement( 'datafield' );
            $writer->writeAttribute( 'tag', '856' );
            $writer->writeAttribute( 'ind1', '4' );
            $writer->writeAttribute( 'ind2', '0' );
            self::subfield( $writer, 'u', $url );
            $writer->endElement();
        }

        $writer->endElement(); // record
    }

    private static function controlfield( \XMLWriter $writer, string $tag, string $value ): void {
        $writer->startElement( 'controlfield' );
        $writer->writeAttribute( 'tag', $tag );
        $writer->text( self::safe( $value ) );
        $writer->endElement();
    }

    private static function subfield( \XMLWriter $writer, string $code, string $value ): void {
        $writer->startElement( 'subfield' );
        $writer->writeAttribute( 'code', $code );
        $writer->text( self::safe( $value ) );
        $writer->endElement();
    }

    private static function year( $val ): string {
        if ( ! is_string( $val ) ) { return ''; }
        if ( preg_match( '/(\d{4})/', $val, $m ) ) { return $m[1]; }
        return '';
    }

    private static function marc_lang( $val ): string {
        if ( ! is_string( $val ) || $val === '' ) { return ''; }
        $map = [
            'en' => 'eng', 'fr' => 'fre', 'es' => 'spa', 'de' => 'ger', 'it' => 'ita',
            'pt' => 'por', 'zh' => 'chi', 'ja' => 'jpn', 'ru' => 'rus', 'ar' => 'ara',
        ];
        $val = strtolower( substr( $val, 0, 3 ) );
        return $map[ $val ] ?? '';
    }

    private static function clean( string $val ): string {
        $val = html_entity_decode( $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
        $val = strip_tags( $val );
        $val = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $val );
        return trim( preg_replace( '/\s+/u', ' ', $val ) );
    }

    private static function plain_text( string $val ): string {
        $val = wptexturize( $val ); // in case getBookInformation returned raw
        $val = wpautop( $val );
        return self::clean( $val );
    }

    private static function safe( string $val ): string {
        return \Pressbooks\Sanitize\remove_control_characters( $val );
    }

    private static function empty_collection(): string {
        return '<?xml version="1.0" encoding="UTF-8"?>\n<collection xmlns="http://www.loc.gov/MARC21/slim" />';
    }
}
