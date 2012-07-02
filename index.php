<?php
/**
 * This page allows you specify a URL and one of the allowed image transforms, and it will then
 * fetch that URL and perform the filter on every image on the page in an <img> tag
 *
 * PHP version 5
 *
 * @author Jonathan Klein <jonathan.n.klein@gmail.com>
 */

// Include a library for parsing a DOM tree (from http://simplehtmldom.sourceforge.net/)
include('simple_html_dom.php');


/**
 * This function takes in a URL that is either absolute or relative, and makes it absolute based on the provided base url
 *
 * @param string $base_url The base URL that we want to prepend to any relative URLs
 * @param string $url      The provided URL that could be either absolute or relative
 *
 * @return string An absolute URL with the correct protocol (http/https)
 */
function make_url_absolute($base_url, $relative_url) {
  // Replace any spaces out of the relative url
  // Not doing a full urlencode here b/c encoding only the characters we want to encode gets complicated
  $relative_url = str_replace(' ', '%20', $relative_url);

  if (substr($relative_url, 0, 4) === 'http') {
    // In this case the URL is already absolute, so we just return it
    return $relative_url;
  } else {
    $base_url_scheme = parse_url($base_url, PHP_URL_SCHEME);
    $base_url_host = parse_url($base_url, PHP_URL_HOST);
    $base_url_path = substr(parse_url($base_url, PHP_URL_PATH), 0, strrpos(parse_url($base_url, PHP_URL_PATH), '/'));

    if (substr($relative_url, 0, 1) === '/') {
      return $base_url_scheme . '://' . $base_url_host . $relative_url;
    } else {
      return $base_url_scheme . '://' . $base_url_host . $base_url_path . '/' . $relative_url;
    }
  }
}


// Initialize some variables
$errors = array();
$error_string = '';
$url = '';
$filter = '';
$html = '';
$cache_hit = false;
$allowed_img_extentions = array('.jpg', '.jpeg', '.png', '.gif');


// See if the user has submitted the page
if (!empty($_POST['submit'])) {

  // Validate the input URL
  if (!filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
    $errors['url'] = 'Please enter a valid URL of the form http://www.example.org';
    $url = '';
  }

  // Make sure they selected a filter
  if (empty($_POST['filter'])) {
    $errors['filter'] = 'Please select a filter you want to do on the images in the specified URL';
  }

  // If we don't have any errors then let's fetch the URL and apply the filter
  if (empty($errors)) {

    // Move the post params into local variables, in case we want to sanitize down the road, or change the name of the form field
    $url = $_POST['url'];
    $filter = $_POST['filter'];

    // Make a directory for this URL and filter (if it doesn't already exist) that will contain the images we download
    // I'm hashing it to keep the length consistent, and also to easily take care of any special characters in the URL
    $folder_name = md5($url . $filter);
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

      // Make sure we blow away any <base> tags if there are any, since they would mess up our relative image paths
      foreach ($html->find('base') as $element) {
        $element->href = '';
      }

      // Make any CSS link tags point to absolute URLs so they still work after we download the code:
      foreach ($html->find('link') as $element) {
        $new_href = make_url_absolute($url, $element->href);

        // Assign the new href to this link tag
        $element->href = $new_href;
      }

      // Find all of the images on the page and process them
      foreach ($html->find('img') as $image_element) {

        // Make sure we have an absolute URL to the image that we want to curl
        $url_to_curl = make_url_absolute($url, $image_element->src);

        // Curl the image so we can write it to disk and run some ImageMagick commands against it
        $ch = curl_init($url_to_curl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $rawdata = curl_exec($ch);
        curl_close ($ch);

        // Hash the URL we are curling to get a unique filename for each image.
        // This solves the case of images with the same name but with different paths
        $image_file_name = md5($url_to_curl);

        // If the image has a query string we need to handle that
        if (($query_string_pos = strrpos($url_to_curl, '?')) !== false) {
          $image_query_string = substr($url_to_curl, $query_string_pos);
          $image_extension = substr($url_to_curl, strrpos($url_to_curl, '.'), $query_string_pos - strrpos($url_to_curl, '.'));
        } else {
          $image_query_string = '';
          $image_extension = substr($url_to_curl, strrpos($url_to_curl, '.'));
        }

        // If the image extension isn't one of the allowed ones we will just go to the next iteration in the loop
        // This prevents things like trying to apply a filter to a beacon that has no file extension
        if (!in_array(strtolower($image_extension), $allowed_img_extentions)) {
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
        // Wrap this in a try/catch since sometimes sites have malformed images that ImageMagick can't open
        try {
          $image = new Imagick($path_to_image);
          $processed_image = clone $image;

          // Do the filter based on what was passed in
          if ($filter === 'flipx') {
            $processed_image->flipImage();
          } elseif ($filter === 'flipy') {
            $processed_image->flopImage();
          } elseif ($filter === 'blur') {
            $processed_image->blurImage(5, 3);
          } elseif ($filter === 'gray') {
            $processed_image->modulateImage(100, 0, 100);
          }

          // Write the image out
          $processed_image->writeImage($folder_path . '/' . $image_file_name . '_processed' . $image_extension);

          // Populate our image array with the path to both the old and new images
          $image_element->src = '/' . $folder_name . '/' . $image_file_name . '_processed' . $image_extension;
        } catch (Exception $e) {
          error_log($e);
        }


      }

      // Write the HTML to disk to cache this request
      $fp = fopen($folder_path . '/index.html','x');
      fwrite($fp, $html);
      fclose($fp);
    }
  } else {
    $error_string = '<div class="errors">';
    foreach ($errors as $error_name => $val) {
      $error_string .= $val . '<br/>';
    }
    $error_string .= '</div>';
  }
}


?>
<!DOCTYPE html>
<html>
  <head>
    <title>Filter Images on an Arbitrary URL</title>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />

    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/3.5.1/build/cssreset/cssreset-min.css">
    <style>
      body {
        font-family:helvetica,sans-serif,arial;
        font-size:14px;
      }

      /* I feel a little silly using every vendor prefix on the gradients, but oh well */
      .header_form {
        padding:10px;
        font-size:18px;
        text-align:center;
        background:#019AC4;
        background:-webkit-gradient(radial,circle,#3BC3E5,#019AC4);
        background:-webkit-radial-gradient(circle,#3BC3E5,#019AC4);
        background:-moz-radial-gradient(circle,#3BC3E5,#019AC4);
        background:-ms-radial-gradient(circle,#3BC3E5,#019AC4);
        background:-o-radial-gradient(circle,#3BC3E5,#019AC4);
        background:radial-gradient(circle,#3BC3E5,#019AC4);
        border-top:1px solid #5EBFD9;
        border-bottom:1px solid #999;
        color:white;
      }

      input[type="submit"] {
        border-radius:5px;
        padding:3px;
        background:#63BB4A;
        background:-webkit-gradient(linear,0% 0%,0% 100%,color-stop(0%,#83C96F),color-stop(50%,#63BB4A),color-stop(100%,#4E9939));
        background:-webkit-linear-gradient(0% 0%,0% 100%,color-stop(0%,#83C96F),color-stop(50%,#63BB4A),color-stop(100%,#4E9939));
        background:-moz-linear-gradient(top,#83C96F 0%,#63BB4A 50%,#4E9939 100%);
        background:-ms-linear-gradient(top,#83C96F 0%,#63BB4A 50%,#4E9939 100%);
        background:-o-linear-gradient(top,#83C96F 0%,#63BB4A 50%,#4E9939 100%);
        background:linear-gradient(top,#83C96F 0%,#63BB4A 50%,#4E9939 100%);
        color:white;
        border-width:1px;
        border-color:#3B742B;
        border-left-color:#63BB4A;
        border-top-color:#63BB4A;
        border-right:1px solid #3B742B;
        border-bottom:1px solid #3B742B;
      }

      .url {
        margin-right:25px;
        width:300px;
      }

      .errors {
        color:yellow;
        padding:10px;
      }
    </style>
  </head>
  <body>
    <div class="header_form">
      <?=$error_string;?>
      <form method="post" action="<?=$_SERVER['SCRIPT_NAME'];?>">
        URL To Fetch:
        <input type="url" value="<?=$url;?>" name="url" placeholder="http://www.example.org" class="url" />

        Filter to Apply:
        <select name="filter">
          <option value="flipx" <?=($filter == 'flipx' ? 'selected' : '');?>>
            Flip along x-axis (vertical flip)
          </option>
          <option value="flipy" <?=($filter == 'flipy' ? 'selected' : '');?>>
            Flip along y-axis (horizontal flip)
          </option>
          <option value="blur" <?=($filter == 'blur' ? 'selected' : '');?>>
            Blur images
          </option>
          <option value="gray" <?=($filter == 'gray' ? 'selected' : '');?>>
            Convert images to grayscale
          </option>
        </select>
        <input type="submit" value="submit" name="submit" />
      </form>
    </div>

    <?=$html;?>
  </body>
</html>
