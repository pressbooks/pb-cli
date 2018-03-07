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

	Scenario: Attempt to lock a theme that is already locked.
    	Given a Pressbooks subdirectory install

		When I run `wp site list --site__in=2 --field=url`
		And save STDOUT as {BOOK_URL}

		When I run `wp pb theme lock --url={BOOK_URL}`
		And I run `wp pb theme lock --url={BOOK_URL}`
		Then STDERR should contain:
			"""
			Theme already locked
			"""

	Scenario: Unlock a theme.
    	Given a Pressbooks subdirectory install

		When I run `wp site list --site__in=2 --field=url`
		And save STDOUT as {BOOK_URL}

		When I run `wp pb theme unlock --url={BOOK_URL}`
		Then STDERR should contain:
			"""
			Theme already unlocked
			"""

	Scenario: Attempt to unlock a theme that is already locked.
    	Given a Pressbooks subdirectory install

		When I run `wp site list --site__in=2 --field=url`
		And save STDOUT as {BOOK_URL}

		When I run `wp pb theme lock --url={BOOK_URL}`
		When I run `wp pb theme unlock --url={BOOK_URL}`
		Then STDOUT should contain:
			"""
			Theme was unlocked
			"""
