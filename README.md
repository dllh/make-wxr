make-wxr
========

A wp-cli command for making randomish WXR files for testing and benchmarking.

Usage:
------

wp make-wxr --site_title=Foo --site_url=http://foo.bar --post_count=100 --comments_per_post=5 --tag_count=30 --cat_count=20 --author_count=3

All arguments are optional. Sensible defaults are used if no arguments are passed.

Notes:
------

* Authors will be listed as author1, author2, etc., and are randomly assigned to posts.
* Post slugs and titles are also drably and numerically named.
* Categories and tags will look something like this: category-2271601f08-1 (counting up from 1 to N, with some random garbage thrown in).
* The tag and category counts govern how many are created (that is, listed at the top and created at the beginning of the import process). Each post will have 5 categories and 5 tags (or fewer, if you specify smaller --tag_count and --cat_count arguments), randomly selected from among those created at the beginning.
* Beware setting the counts too high. At present, the command just spits out whatever you tell it to.


