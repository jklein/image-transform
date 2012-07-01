<?php
/**
 * Torbit Programming Test - Image manipulation on remote URL
 *
 * PHP version 5
 *
 * @author     Jonathan Klein <jonathan.n.klein@gmail.com>
 */

// Include a library for parsing a DOM tree (from http://simplehtmldom.sourceforge.net/)
include('simple_html_dom.php');


// Initialize some variables
$errors = array();
$image_src_array = array();

// See if the user has submitted the page
if (!empty($_POST['submit'])) {

	// Validate the input URL 
	if (!filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
		$errors['url'] = 'Please enter a valid URL of the form http://www.example.org';
	}

	// Make sure they selected a transformation (or more than one)
	if (empty($_POST['transformation'])) {
		$errors['transformation'] = 'Please select a transformation you want to do on the images in the specified URL';
	}

	// If we don't have any errors then let's fetch the URL and do the transformation 
	if (empty($errors)) {

		// set URL and other appropriate options
		// $url = $_POST['url'];
		$url = 'http://www.wayfair.com/';

		$html = file_get_html($url);

		foreach ($html->find('img') as $image_element) {
    		$image_src_array[] = $image_element->src;
    	}

		echo '<pre>';
		print_r($image_src_array);
		echo '</pre>';
	
	} else {
		print_r($errors);
	}
}



$image = new Imagick('source.png');
$reflection = $image->clone();
$reflection->flipImage();
$reflection->writeImage('dest.png');

?>

<DOCTYPE html> 
<html>
	<head>
		<title>Image Filtering</title>
		<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
	</head>
	<body>
		<form method="post" action="<?=$_SERVER['SCRIPT_NAME'];?>">
			<input type="url" value="" name="url" placeholder="URL Here" />
			<select name="transformation" multiple="multiple">
				<option value="flip">Flip along x-axis (vertical flip)</option>
				<option value="gray">Convert Images to grayscale</option>
			</select>
			<input type="submit" value="submit" name="submit" />
		</form>
	</body>
</html>