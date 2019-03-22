Feature: Test that l18n overrides are work

  Scenario: Make Pressbooks pot file, with support for Blade templates
    Given a Pressbooks subdirectory install

    When I run `wp pb make-pot ./wp-content/plugins/pressbooks ./wp-content/plugins/pressbooks/languages/pressbooks.pot --skip-audit --exclude="vendor,symbionts" --headers='{"Report-Msgid-Bugs-To":"https://github.com/pressbooks/pressbooks/"}'`
    Then STDOUT should contain:
			"""
			Extracting strings from .blade.php templates...
			"""