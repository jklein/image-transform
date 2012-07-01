===============
Overview
===============
This is a small tool that allows you to apply an ImageMagick filter to all images on a specified web page.  When the URL is submitted, the page parses the DOM using Simple HTML DOM, a PHP class that I got from http://simplehtmldom.sourceforge.net/.  Then it curls all of the images individually, writes them to disk, runs the filter on them, and rewrites the HTML source of the URL to reference the local images.

There is a pretty trivial caching system in place - if you hit the same URL with the same filter you will get a cached HTML page and you will use images that have already been filtered.


===============
Requirements
===============
* PHP 5.3+ (could work with older versions, but they were not tested)
* php-curl
* php-imagick
* PHP Simple HTML DOM Parser (included in the repo)
* ImageMagick
* Apache 2


===============
Known Issues
===============
* Encoding can get a little screwed up if special characters exist.  Since I am serving the document as UTF-8, if the source page has a different encoding we can get into trouble.
* The method of curling the images is pretty slow, I could definitely parallelize this.
* The script currently only supports three file types (but they are the most common by far)
* This only works for img tags, not CSS background images or images fetched by JS
* This will not work for data URIs
* This is probably the least secure application I have ever written.  There are TONS of security vulnerabilities
* As more URLs get hit I never clean up the disk, so we will get a proliferation of folders over time
* This is intentionally not MVC, I wanted to keep everything in one file for ease of review
* In a similar vein, this is why I inlined the CSS, and why I am just including the YUI3 reset file with a link tag.  I know this isn't the best practice from a performance point of view, but I wanted to keep the code as easy to review as possible.
* Because the HTML from the page being curled is simply echoed to the screen, the page inherits whatever CSS that URL has, which can negatively impact the layout of the header that I created.
* The favicon gets screwed up because of browser caching and the favicon tags in the source documents that we are curling
* If the JS is referenced from a relative URL then we will lose it in the processed page. I fixed this issue for CSS, but I am intentionally skipping it for JS.  Hey, it should make the page faster :-).



===============
Features to Add
===============
* Make the call with Ajax and show a busy indicator on the page with a "processing..." message or something
* HTTP Auth
* Allow people to bypass the cache if they want
* It would be cool to add support for arbitrary filters, so if the user knew the syntax for the PHP Imagick plugin they could just type any filter they wanted in.
* I would also like to refactor the code slightly so that adding a new filter would only require a 1-2 line code change in one place.  This might make things a little less readable, but it would be easier to update in the future.
