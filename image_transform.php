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
$img_src_array = array();
$html = '';

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

		// Make a directory for this hostname (if it doesn't already exist) that will contain the images we download
		$folder_name = str_replace(parse_url($url, PHP_URL_HOST), '.', '');
		$full_path = '/var/www/' . $folder_name;
		if (!file_exists($full_path)) {
			mkdir($full_path);
		}

		// Get the HTML from the URL (this function is from simple_html_dom.php)
		$html = file_get_html($url);

		// Find all of the images on the page and process them
		foreach ($html->find('img') as $image_element) {
			echo 'image src:' . $image_element->src . '<br/>';

			// See if we are dealing with a relative image path
			if (substr($image_element->src, 0, 4) !== 'http') {
				if (substr($image_element->src, 0, 1) === '/') {
					$url_to_curl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $image_element->src;
				} else {
					$url_to_curl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH) . '/' . $image_element->src;
				}
			} else {
				$url_to_curl = $image_element->src;
			}


			// Curl the image so we can write it to disk and run some Image Magick commands against it
			echo 'curling url:' . $url_to_curl . '<br/>';

			$ch = curl_init($url_to_curl);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
			$rawdata = curl_exec($ch);
			curl_close ($ch);

			// Hash the URL we are curling to get a unique filename for each image.
			// This solves the case of images with the same name but with different paths
			$image_file_name = md5($url_to_curl);
			$image_extension = substr($url_to_curl, strrpos($url_to_curl, '.'));
			$path_to_image = $full_path . $image_file_name . $image_extension;

			echo 'path to image:' . $path_to_image . '<br/>';

			if (!file_exists($path_to_image)) {
				$fp = fopen($path_to_image,'x');
				fwrite($fp, $rawdata);
				fclose($fp);
			}

			// Now that we have the image on disk let's open it up, transform it, and write it back with a new name
			$image = new Imagick($path_to_image);
			$reflection = $image->clone();
			$reflection->flipImage();
			$reflection->writeImage($full_path . $image_file_name . '_processed' . $image_extension);

			// Populate our image array with the path to both the old and new image
			$img_src_array[$image_element->src] = '/' . $folder_name . '/' . $image_file_name . '_processed' . $image_extension;
    }


    // At this point we should have all of the images downloaded, processed, and writted to disk.
    // Now we just need to replace the source attributes in the HTML with the paths to the new images we have created:
    foreach ($img_src_array as $old_src => $new_src) {
    	str_replace($old_src, $new_src, $html);
    }

	} else {
		print_r($errors);
	}
}


echo '<pre>';
print_r($img_src_array);
echo '</pre>';

/*
$image = new Imagick('source.png');
$reflection = $image->clone();
$reflection->flipImage();
$reflection->writeImage('dest.png');
*/
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

		<?=$html;?>
	</body>
</html>
