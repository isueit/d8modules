<?php

namespace Drupal\microsites_media_filter\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\MediaLibrary;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;
use Drupal\media_library\MediaLibraryState;

/**
 * Adds the current group ID to the editor's media library URL.
 *
 * Core builds the library URL from a hash-signed MediaLibraryState, so the
 * group ID has to go into the opener parameters (not tacked onto the URL)
 * for the hash to stay valid.
 */
class GroupMediaLibrary extends MediaLibrary {

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $config = parent::getDynamicPluginConfig($static_plugin_config, $editor);

    $group_id = microsites_media_filter_current_group_id();
    if (!$group_id || empty($config['drupalMedia']['libraryURL'])) {
      return $config;
    }

    // Rebuild core's state with the group ID added.
    $query = [];
    parse_str((string) parse_url($config['drupalMedia']['libraryURL'], PHP_URL_QUERY), $query);

    $opener_parameters = $query['media_library_opener_parameters'] ?? [];
    // Must be a string: core hashes serialize($opener_parameters), and the
    // value comes back from the URL as a string, so an int breaks the hash
    // and the dialog returns 403.
    $opener_parameters['group_id'] = (string) $group_id;

    $state = MediaLibraryState::create(
      $query['media_library_opener_id'],
      $query['media_library_allowed_types'],
      $query['media_library_selected_type'],
      (int) $query['media_library_remaining'],
      $opener_parameters
    );

    $config['drupalMedia']['libraryURL'] = Url::fromRoute('media_library.ui')
      ->setOption('query', $state->all())
      ->toString(TRUE)
      ->getGeneratedUrl();

    return $config;
  }

}
