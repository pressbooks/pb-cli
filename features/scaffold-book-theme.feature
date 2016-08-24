Feature: Scaffold book themes.

	Scenario: Scaffold starter code for a book theme
		Given a WP install
		Given I run `wp theme path`
		And save STDOUT as {THEME_DIR}
		When I run `wp scaffold book-theme pressbooks-mcluhan`
		Then STDOUT should contain:
			"""
			Created theme Pressbooks-mcluhan.
			"""
		And the {THEME_DIR}/pressbooks-mcluhan/style.css file should exist
