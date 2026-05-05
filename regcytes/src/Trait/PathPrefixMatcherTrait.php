<?php

declare(strict_types=1);

namespace Drupal\regcytes\Trait;

/**
 * Provides a function to find the best matching path in a list of prefixes.
 *
 * In a trait for ease of testing.
 */
trait PathPrefixMatcherTrait {

  /**
   * Searches an array of path prefixes and returns the match's key or FALSE.
   *
   * For example, the request path "/foo/barbazqux" string starts with the
   * prefixes: "/foo", "/foo/bar", and "/foo/barbaz". However, the "matching"
   * prefix is "/foo" despite "/foo/barbaz" being the longest prefix.
   *
   * @param string $needle
   *   The request path to match.
   * @param array $haystack
   *   An array of path prefixes to match with the request path.
   *
   * @return false|int|string
   *   The array index of the best matching path in haystack.
   */
  protected static function matchPathPrefix(string $needle, array $haystack): false|int|string {
    // Explode all paths into their segments.
    $to_segments = static fn($p) => \explode('/', \trim($p, '/'));
    $needle_segments = $to_segments(\trim($needle, '/'));
    $haystack_segments = \array_map($to_segments, $haystack);
    // Filter out prefixes which have more path segments than the request path.
    // They can't possibly be a match.
    $haystack_segments = \array_filter($haystack_segments, static fn($s) => \count($s) <= \count($needle_segments));
    // Sort the possible matches by segment count, descending. Do this so that,
    // the most specific prefix will be found before a less specific prefix in
    // the loop below.
    $order_segment_count_desc = static fn(array $a, array $b) => \count($b) <=> \count($a);
    \uasort($haystack_segments, $order_segment_count_desc);
    // Look at each possible match.
    foreach ($haystack_segments as $key => $match_segments) {
      // Walk over the possible match segments, comparing each one with the
      // corresponding request path segment.
      for ($i = 0; $i < \count($match_segments) && $i < \count($needle_segments); $i++) {
        // Every segment must be an exact match. If not, break and check the
        // next possible match.
        if ($match_segments[$i] !== $needle_segments[$i]) {
          continue 2;
        }
      }
      // All segments must have been and exact match, break the loop and
      // immediately return it.
      return $key;
    }
    // No match.
    return FALSE;
  }

}
