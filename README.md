===============
OVERVIEW
===============




===============
KNOWN ISSUES
===============
* Encoding can get a little screwed up with special characters.  Since I am serving the document as UTF-8 if the source page has a different encoding we can get into trouble.
* Need to add caching
* The method of curling the images is pretty slow, I could definitely parallelize this
* If a path to an image is specified as ../.. and we are already in a nested directory my script will likely fail
* Verify that input that is deeper in the directory tree (like www.example.com/foo/bar) works
* This only works for <img> tags, not CSS background images or images fetched by JS
* This will not work for data URIs
* This is probably the least secure application I have ever written.  There are TONS of security vulnerabilities
* As more URLs get hit I never clean up the disk, so we will get a proliferation of folders over time
* This is intentionally not MVC, I wanted to keep everything in one file for ease of review


===============
FEATURES TO ADD
===============
* Make the call with Ajax and show a busy indicator on the page with a "processing..." message or somethign
* Need to add error messaging
* Need to show what the user requested
* HTTP Auth
* Add at least four transformations, and make it extremely easy to plug-in additional transformations
* Allow people to bypass the cache if they want

