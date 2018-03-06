Feature: Test that Pressbooks installs.

  Scenario: Install a Pressbooks subdirectory install
    Given a Pressbooks subdirectory install

		When I run `wp plugin list --status=must-use`
		Then STDOUT should contain:
			"""
			hm-autoloader
			"""

		When I run `wp plugin list`
		Then STDOUT should contain:
			"""
			pressbooks
			"""

		When I run `wp theme list`
		Then STDOUT should contain:
			"""
			pressbooks-book
			"""
		And STDOUT should contain:
			"""
			pressbooks-aldine
			"""
		# When I run `wp eval 'echo PB_BOOK_THEME;'`
		# Then STDOUT should contain:
		# 	"""
		# 	pressbooks-book
		# 	"""
