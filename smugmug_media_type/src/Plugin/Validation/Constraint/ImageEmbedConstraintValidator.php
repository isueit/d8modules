<?php

namespace Drupal\smugmug_media_type\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\smugmug_media_type\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the smugmug embed providers.
 */
class ImageEmbedConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Smugmug embed provider manager service.
   *
   * @var \Drupal\smugmug_media_type\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * Create an instance of the validator.
   *
   * @param \Drupal\smugmug_media_type\ProviderManagerInterface $provider_manager
   *   The provider manager service.
   */
  public function __construct(ProviderManagerInterface $provider_manager) {
    $this->providerManager = $provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('smugmug_media_type.provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($field, Constraint $constraint) {
    //Prevent empty value
    if (!isset($field->value)) {
      return NULL;
    }
    
    //Prevent urls that are don't have an applicable provider from being 
    //Find a provider that can extract valid id from input url/string
    $allowed_providers = $field->getFieldDefinition()->getSetting('allowed_providers');
    $allowed_provider_definitions = $this->providerManager->loadDefinitionsFromOptionList($allowed_providers);

    if (FALSE === $this->providerManager->filterApplicableDefinitions($allowed_provider_definitions, $field->value)) {
      $this->context->addViolation($constraint->message_no_provider);
      
    } else {
      // Prevent urls with ids that dont point to valid images
      // Check that getRemoteThumbnailUrl() returns a url that holds a valid resource, ie does not 404
      // Can't use api as we need to know if it is valid regardless of having api key 
      $provider = $this->providerManager->loadProviderFromInput($field->value);
      $thumbnailUrl = $provider->getRemoteThumbnailUrl();
      $headers = @get_headers($thumbnailUrl); //Headers only, ignoring errors

      if (!$headers || $headers[0] == 'HTTP/1.1 404 Not Found') {
        $this->context->addViolation($constraint->message_invalid_image);
      }
    }  
  }
}
