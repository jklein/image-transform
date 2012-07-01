===============
OVERVIEW
===============
This is a small tool that allows you to perform Image Magick filters on all images in a specified URL.  When the URL is submitted, I parse the DOM using Simple HTML DOM, a PHP class that I got from http://simplehtmldom.sourceforge.net/.  Then I cURL all of the images individually, write them to disk, run the filter on them, and rewrite the HTML source of the URL to reference the local images.

There is a pretty trivial caching system in place, if you hit the same URL with the same filter you will get a cached HTML page and you will use images that have already been filtered.



===============
KNOWN ISSUES
===============
* Encoding can get a little screwed up if special characters exist.  Since I am serving the document as UTF-8, if the source page has a different encoding we can get into trouble.
* The method of curling the images is pretty slow, I could definitely parallelize this.
* The script currently only supports three file types (but they are the most common by far)
* This only works for <img> tags, not CSS background images or images fetched by JS
* This will not work for data URIs
* This is probably the least secure application I have ever written.  There are TONS of security vulnerabilities
* As more URLs get hit I never clean up the disk, so we will get a proliferation of folders over time
* This is intentionally not MVC, I wanted to keep everything in one file for ease of review
* In a similar vein, this is why I inlined the CSS, and why I am just including the YUI3 reset file with a link tag.  I know this isn't the best practice from a performance point of view, but I wanted to keep the code as easy to review as possible.


===============
NEED TO LOOK INTO
===============
* If a path to an image is specified as ../../foo.jpg and we are already in a nested directory my script will likely fail
* Need to explicitly test all of the flavors of relative URLs


===============
FEATURES TO ADD
===============
* Make the call with Ajax and show a busy indicator on the page with a "processing..." message or something
* HTTP Auth
* Allow people to bypass the cache if they want
* It would be cool to add support for arbitrary filters, so if the user knew the syntax for the PHP Imagick plugin they could just type any filter they wanted in.
