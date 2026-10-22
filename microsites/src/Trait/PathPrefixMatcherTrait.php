<?php

declare(strict_types=1);

namespace Drupal\microsites\Trait;

/**
 * Finds the best matching path prefix in a list of candidates.
 */
trait PathPrefixMatcherTrait {

  /**
   * Searches an array of path prefixes and returns the best match's key.
   *
   * "Best" means the most-specific prefix (most path segments) that is still
   * an exact segment-boundary match for the needle.
   *
   * Example: for needle "/foo/bar/baz", the candidates "/foo" and "/foo/bar"
   * both qualify; "/foo/bar" wins because it is more specific.
   *
   * @param string $needle
   *   The request path to match against.
   * @param array $haystack
   *   An array of path prefix strings to test, keyed by any value.
   *
   * @return false|int|string
   *   The key of the best-matching prefix, or FALSE if no match.
   */
  protected static function matchPathPrefix(string $needle, array $haystack): false|int|string {
    $to_segments     = static fn($p) => explode('/', trim($p, '/'));
    $needle_segments = $to_segments(trim($needle, '/'));
    $haystack_segs   = array_map($to_segments, $haystack);

    // Discard candidates with more segments than the needle.
    $haystack_segs = array_filter(
      $haystack_segs,
      static fn($s) => count($s) <= count($needle_segments)
    );

    // Most-specific first.
    uasort($haystack_segs, static fn($a, $b) => count($b) <=> count($a));

    foreach ($haystack_segs as $key => $match_segments) {
      for ($i = 0; $i < count($match_segments) && $i < count($needle_segments); $i++) {
        if ($match_segments[$i] !== $needle_segments[$i]) {
          continue 2;
        }
      }
      return $key;
    }

    return FALSE;
  }

}
