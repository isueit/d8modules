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
}
