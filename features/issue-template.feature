Feature: Scaffold issue template.

	Scenario: Scaffold issue template for a theme
		Given a WP install

		When I run `wp theme path`
		And save STDOUT as {THEME_DIR}
		And I run `wp scaffold _s test-theme`
		And I run `wp pb issue-template test-theme --type=theme --owner=pressbooks`
		Then the {THEME_DIR}/test-theme/.github/ISSUE_TEMPLATE.md file should exist

	Scenario: Scaffold issue template for a plugin
		Given a WP install

		When I run `wp plugin path`
		And save STDOUT as {PLUGIN_DIR}
		And I run `wp scaffold plugin test-plugin`
		And I run `wp pb issue-template test-plugin --type=plugin --owner=pressbooks`
		Then the {PLUGIN_DIR}/test-plugin/.github/ISSUE_TEMPLATE.md file should exist
