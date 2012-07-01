<?php
/**
 * This page allows you specify a URL and one of the allowed image transforms, and it will then
 * fetch that URL and perform the transformation on every image on the page in an <img> tag
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
$cache_hit = false;
$allowed_img_extentions = array('.jpg', '.png', '.gif');

// See if the user has submitted the page
if (!empty($_POST['submit'])) {

  // Validate the input URL
  if (!filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
    $errors['url'] = 'Please enter a valid URL of the form http://www.example.org';
  }

  // Make sure they selected a transformation
  if (empty($_POST['transformation'])) {
    $errors['transformation'] = 'Please select a transformation you want to do on the images in the specified URL';
  }

  // If we don't have any errors then let's fetch the URL and do the transformation
  if (empty($errors)) {

    // set URL and other appropriate options
    $url = $_POST['url'];

    // Make a directory for this hostname (if it doesn't already exist) that will contain the images we download
    $folder_name = str_replace('.', '', parse_url($url, PHP_URL_HOST));
    $folder_path = '/var/www/' . $folder_name;
    if (!file_exists($folder_path)) {
      mkdir($folder_path);
    } else {
      $cache_hit = true;
      $html = file_get_contents($folder_path . '/index.html');
    }

    if (!$cache_hit) {
      // Get the HTML from the URL (this function is from simple_html_dom.php)
      $html = file_get_html($url);

      //Make sure we blow away a <base> tag if there is one, since it will mess up our relative image paths
      foreach ($html->find('base') as $element) {
        $element->href = '';
      }


      // Find all of the images on the page and process them
      foreach ($html->find('img') as $image_element) {

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

        // If the image extension isn't one of the allowed ones we will just go to the next iteration in the loop
        // This prevents things like beacons from being transformed
        if (!in_array($image_extension, $allowed_img_extentions)) {
          continue;
        }

        // Create the path that we are going to write the image to
        $path_to_image = $folder_path . '/' . $image_file_name . $image_extension;

        if (!file_exists($path_to_image)) {
          $fp = fopen($path_to_image,'x');
          fwrite($fp, $rawdata);
          fclose($fp);
        }

        // Now that we have the image on disk let's open it up, transform it, and write it back with a new name
        $image = new Imagick($path_to_image);
        $processed_image = $image->clone();
        $processed_image->flipImage();
        $processed_image->writeImage($folder_path . '/' . $image_file_name . '_processed' . $image_extension);

        // Populate our image array with the path to both the old and new image
        $img_src_array[$image_element->src] = '/' . $folder_name . '/' . $image_file_name . '_processed' . $image_extension;
      }


      // At this point we should have all of the images downloaded, processed, and written to disk.
      // Now we just need to replace the source attributes in the HTML with the paths to the new images we have created:
      foreach ($img_src_array as $old_src => $new_src) {
        $html = str_replace($old_src, $new_src, $html);
      }

      // Write the HTML to disk to cache this request
      $fp = fopen($folder_path . '/index.html','x');
      fwrite($fp, $html);
      fclose($fp);
    }
  } else {
    print_r($errors);
  }
}

?>

<DOCTYPE html>
<html>
  <head>
    <title>Image Filtering</title>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />

    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/3.5.1/build/cssreset/cssreset-min.css">
    <style>
      body {
        font-family:helvetica,sans-serif,arial;
        font-size:14px;
      }

      .header_form {
        padding:10px;
        font-size: 18px;
        text-align: center;
        background:#019AC4;
        background:-webkit-radial-gradient(circle,#3BC3E5,#019AC4);
        background:-moz-radial-gradient(circle,#3BC3E5,#019AC4);
        background:-ms-radial-gradient(circle,#3BC3E5,#019AC4);
        border-top:1px solid #5EBFD9;
        border-bottom:1px solid #999;
        color:white;
      }

      input[type="submit"] {
        border-radius: 5px;
        padding:3px;
        background:#63BB4A;
        background:-webkit-gradient(linear,0% 0%,0% 100%,color-stop(0%,#83C96F),color-stop(50%,#63BB4A),color-stop(100%,#4E9939));
        background:-moz-linear-gradient(top,#83C96F 0%,#63BB4A 50%,#4E9939 100%);
        background:linear-gradient(top,#83C96F 0%,#63BB4A 50%,#4E9939 100%);
        color:white;
        border-width: 1px;
        border-color:#3B742B;
        border-left-color: #63BB4A;
        border-top-color: #63BB4A;
        border-right:1px solid #3B742B;
        border-bottom:1px solid #3B742B;
      }

      .url {
        margin-right:25px;
        width:300px;
      }
    </style>
  </head>
  <body>
    <div class="header_form">
      <form method="post" action="<?=$_SERVER['SCRIPT_NAME'];?>">
        URL To Fetch:
        <input type="url" value="" name="url" placeholder="http://www.example.org" class="url" />

        Filter to Apply:
        <select name="transformation">
          <option value="flip">Flip along x-axis (vertical flip)</option>
          <option value="gray">Convert Images to grayscale</option>
        </select>
        <input type="submit" value="submit" name="submit" />
      </form>
    </div>

    <?=$html;?>
  </body>
</html>
