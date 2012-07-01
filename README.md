image-transform
===============
need to test for relative URLs or absolute
If they are relative URLs we should see if there is a baseurl tag on the page 

Want to download all of the URLs and write them to disk.  Do all transformations and rename to something consistent
Dump them into a folder that is named based on the base URL that we fetched them from.  This will enable caching so subsequent lookups are fast

Process the images individually with ImageMagick


		Want to show some sort of busy indicator on the page as we do the logic in the background.  
		Once the page is done loading we can use some JS to hide the spinning gif and show the results

		Have a bar at the top that shows the submitted URL and the desired transformations

		*** Alternate methods of fetching the images ***
		Use Webdriver to actually have a browser fetch the content.  Then look at the resulting output and find the image requests.  
		Or perhaps generate a HAR file from the page and parse that to get image URLs
		Look at HTTPArchive?  
		At that point I need to replace out the source URLs.  Parsing HTML source might be easier

		Need to handle input that is deeper in the directory tree (like www.example.com/foo/bar)

		What about CSS background images?  Ignore for now.  

		Data URIs?  My code will not handle them.  I could see if a data URI has been provided and try to decode it...
		
		Where am I going to host this?  EC2 instance?  Subdomain of jonathanklein.net?

		Ideal would be to set up a subdomain in GoDaddy at torbit-test.jonathanklein.net and point it to EC2
		Explain that I went with a subdomain since the primary domain points at Blogger

		Put it behind HTTP Auth? 

		Mention how insecure it is likely to be

		Add at least four transformations, and make it extremely easy to plug-in additional transformations

		


