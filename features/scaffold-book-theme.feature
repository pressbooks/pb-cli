Feature: Scaffold book themes.

	Scenario: Scaffold starter code for a book theme
		Given a WP install

		When I run `wp theme path`
		And save STDOUT as {THEME_DIR}
		And I run `wp scaffold book-theme nix pressbooks`

		Then STDOUT should contain:
			"""
			Created theme Nix.
			"""
		And the {THEME_DIR}/nix/style.css file should exist
		And the {THEME_DIR}/nix/style.css file should contain:
			"""
			Github Theme URI: pressbooks/nix
			"""
		And the {THEME_DIR}/nix/package.json file should contain:
			"""
			"name": "@pressbooks/nix"
			"""
		And the {THEME_DIR}/nix/composer.json file should contain:
			"""
			"name": "pressbooks/nix"
			"""
