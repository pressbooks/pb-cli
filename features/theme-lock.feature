Feature: Test that themes can be locked and unlocked.

  	Scenario: Lock a theme.
    	Given a Pressbooks subdirectory install

		When I run `wp site list --site__in=2 --field=url`
		And save STDOUT as {BOOK_URL}

		When I run `wp pb theme lock --url={BOOK_URL}`
		Then STDOUT should contain:
			"""
			Theme was locked
			"""

	Scenario: Unlock a theme.
		Given a Pressbooks subdirectory install

		When I run `wp site list --site__in=2 --field=url`
		And save STDOUT as {BOOK_URL}

		When I run `wp pb theme lock --url={BOOK_URL}`
		When I run `wp pb theme unlock --url={BOOK_URL}`
		Then STDOUT should contain:
			"""
			Theme was unlocked
			"""
