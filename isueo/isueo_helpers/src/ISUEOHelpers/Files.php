<?php

namespace Drupal\isueo_helpers\ISUEOHelpers;

use Drupal;
use Drupal\Core\Site\Settings;

class Files
{

  // 900 seconds = 15 minutes
  public const CACHE_SECONDS = 900;

  // Get a file from a URL
  // TODO: Add some sort of caching mechanism
  public static function fetch_url(string $url, bool $use_cache = false)
  {
    // Initialize some variables
    $update_cache = false;
    $cache_file_path = '';
    $creds = '';
    $url = str_replace('http://', 'https://', $url);

    // Check whether it's OK to use a cached file
    if ($use_cache) {
      // Get the path to the cached file, the Upcoming Program Offerings file may include a
      // timestamp as a query parameter, but we don't have to worry about that
      $cache_url = $url;
      if (str_starts_with(strtolower($cache_url), 'https://datastore.exnet.iastate.edu/mydata/upcomingprogramofferings.json')) {
        $cache_url = 'https://datastore.exnet.iastate.edu/mydata/UpcomingProgramOfferings.json';
      }
      $cache_file_path = '/tmp/isueo_helpers/' . md5($cache_url);


      // If the file exists and is new enough, use it
      if (file_exists($cache_file_path) && ((time() - filemtime($cache_file_path)) < Files::CACHE_SECONDS)) {
        return file_get_contents($cache_file_path);
      } else {
        $update_cache = true;
      }
    }

    // The datastore needs special credentials to access via https
    if (str_starts_with(strtolower($url), 'https://datastore')) {
      // Get credentials for the datastore
      $creds = Settings::get('datastore_creds');
      if (empty($creds)) {
        // Don't have credentials, log it, and return an empty json string;
        Drupal::logger('isueo_helpers')->warning('Need to put datastore_creds into the settings.php file!');
        return '[]';
      } else {
        $creds .= '@';
      }

      // Insert the credentials into the URL
      $url = str_replace('https://', 'https://' . $creds, $url);
    }

    // Get the page
    $results = file_get_contents($url);

    // Check if we should write the results to cache
    if ($use_cache && $update_cache) {
      // Make sure the folder exists
      if (file_exists('/tmp')) {
        if (!file_exists('/tmp/isueo_helpers') && is_writable('/tmp')) {
          mkdir('/tmp/isueo_helpers');
        }
      }

      // If the folder is writable, and we have valid results, then write the file to the cache folder
      if (!empty($results) && is_writable('/tmp/isueo_helpers') && !empty(json_decode($results, true))) {
        file_put_contents($cache_file_path, $results);
      }

    }

    // Finally, return the results
    return $results;
  }
}
