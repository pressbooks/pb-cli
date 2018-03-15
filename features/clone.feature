Feature: Test that a book can be cloned.


  Scenario: Clone a book.
    Given a Pressbooks subdirectory install

    When I run `wp site list --site__in=2 --field=url`
    And save STDOUT as {BOOK_URL}

    When I run `wp pb clone {BOOK_URL} clonetest --user=admin`
    Then STDOUT should contain:
			"""
			Cloning succeeded!
			"""
