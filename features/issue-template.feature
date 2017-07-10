Feature: Scaffold issue template.

	Scenario: Scaffold issue template for a theme
		Given a WP install
		Given I run `wp theme path`
		And save STDOUT as {THEME_DIR}
		When I run `wp scaffold _s test-theme`
		When I run `wp pb issue-template`
		And the {THEME_DIR}/test-theme/ISSUE_TEMPLATE.md file should exist
