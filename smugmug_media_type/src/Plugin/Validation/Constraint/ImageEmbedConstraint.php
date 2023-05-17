<?php

namespace Drupal\smugmug_media_type\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the smugmug embed field.
 *
 * @Constraint(
 *   id = "SmugmugEmbedValidation",
 *   label = @Translation("SmugmugEmbed provider constraint", context = "Validation"),
 * )
 */
class ImageEmbedConstraint extends Constraint {

  /**
   * Message shown when a video provider is not found.
   *
   * @var string
   */
  public $message_no_provider = 'Could not find a image provider to handle the given URL.';
  
  /**
   * Message shown when the id does not result in a valid image resource.
   *
   * @var string
   */
  public $message_invalid_image = 'Could not find a valid image from the given url.';
}
