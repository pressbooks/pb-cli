Feature: Scaffold issue template.

	Scenario: Scaffold issue template for a theme
		Given a WP install
		Given I run `wp theme path`
		And save STDOUT as {THEME_DIR}
		When I run `wp scaffold _s test-theme`
		When I run `wp pb issue-template test-theme --type=theme --owner=pressbooks`
		And the {THEME_DIR}/test-theme/.github/ISSUE_TEMPLATE.md file should exist

	Scenario: Scaffold issue template for a plugin
		Given a WP install
		Given I run `wp plugin path`
		And save STDOUT as {PLUGIN_DIR}
		When I run `wp scaffold plugin test-plugin`
		When I run `wp pb issue-template test-plugin --type=plugin --owner=pressbooks`
		And the {PLUGIN_DIR}/test-plugin/.github/ISSUE_TEMPLATE.md file should exist
