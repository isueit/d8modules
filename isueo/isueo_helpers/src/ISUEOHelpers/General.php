<?php

namespace Drupal\isueo_helpers\ISUEOHelpers;

class General
{

  // Turn a 10 digit string into a formatted phone number
  public static function format_phone_number(string $digits)
  {
    switch (strlen($digits)) {
      case 10:
        return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
        break;
      default:
        return $digits;
        break;
    }
    return $digits;
  }

  // Try to format a string into a zip code
  public static function format_zip_code(string $zip)
  {
    switch (strlen($zip)) {
      case 9:
      case 11:
        return sprintf('%s-%s', substr($zip, 0, 5), substr($zip, 5, 4));
        break;
      default:
        return $zip;
        break;
    }
    return $zip;
  }

  // Change County Names to match Counties In Iowa taxonomy
  public static function fix_county_name($county_name) {
    switch ($county_name) {
      case 'East Pottawattamie':
        $county_name = 'Pottawattamie - East';
        break;
      case 'West Pottawattamie':
        $county_name = 'Pottawattamie - West';
        break;
      default:
        break;
    }
    return $county_name;
  }

  // Build a Smugmug URL given the image ID and an optional Size
  //    100 => 'Ti',//Tiny
  //    150 => 'Th',//Thumbnail
  //    300 => 'S',
  //    450 => 'M',
  //    600 => 'L',
  //    768 => 'XL',
  //    960 => 'X2',//2XL
  //    1200 => 'X3',
  //    2048 => 'X4',
  //    2560 => 'X5',
  //    3840 => '4K',
  //    5120 => '5K',
  //    PHP_INT_MAX => '',//Original image size, can be any actual dimension
  public static function build_smugmug_url(string $id, string $size = 'XL')
  {
    return 'https://photos.smugmug.com/photos/' . $id . '/10000/' . $size . '/' . $id . '-' . $size . '.jpg';
  }

  public static function is_production_server() {
    return str_starts_with(DRUPAL_ROOT, '/var/www/websites');
  }
}
