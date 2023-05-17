<?php
namespace Drupal\smugmug_media_type\Form;

use Drupal\media_library\Form\AddFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\smugmug_media_type\Plugin\media\Source\SmugmugEmbedField;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\OpenerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a form to create media entities from Smugmug URLs.
 */
class SmugmugMediaLibraryForm extends AddFormBase {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_smugmug';
  }
  
  /**
   * Constructs a new SmugmugMediaLibraryForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\media_library\MediaLibraryUiBuilder $library_ui_builder
   *   The media library UI builder.
   * @param \Drupal\media_library\OpenerResolverInterface $opener_resolver
   *   The opener resolver.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MediaLibraryUiBuilder $library_ui_builder, OpenerResolverInterface $opener_resolver = NULL) {
    parent::__construct($entity_type_manager, $library_ui_builder, $opener_resolver);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder'),
      $container->get('media_library.opener_resolver')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getMediaType(FormStateInterface $form_state) {
    if ($this->mediaType) {
      return $this->mediaType;
    }
  
    $media_type = parent::getMediaType($form_state);
    if (!$media_type->getSource() instanceof SmugmugEmbedField) {
      throw new \InvalidArgumentException('Can only add media types which use an SmugmugEmbedField.');
    }
    return $media_type;
  }
  
  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $media_type = $this->getMediaType($form_state);

    $providers = $media_type->getSource()->getProviders();
  
    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
    ];
  
    $form['container']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Add @type via URL', [
        '@type' => $this->getMediaType($form_state)->label(),
      ]),
      '#description' => $this->t('Allowed providers: @providers.', [
        '@providers' => implode(', ', $providers),
      ]),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'https://',
      ],
    ];
    $form['container']['alt'] = [
      '#title' => t('Alternative text'),
      '#type' => 'textfield',
      '#description' => t('Short description of the image used by screen readers and displayed when the image is not loaded. This is important for accessibility.'),
      '#maxlength' => 255,
      '#required' => TRUE
    ];
  
    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#validate' => ['::validateUrl'],
      '#submit' => ['::addButtonSubmit'],
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];
    return $form;
  }
  
  /**
   * Validates the Smugmug URL.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateUrl(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('url');
    if ($url) {
      try {
        //Get definitions applicable to given url, if it returns false or an empty array, then there aren't applicable defintions and url is invalid for smugmug
        $defs = $this->getMediaType($form_state)->getSource()->getDefinitions($url);
        if (!$defs || count($defs) == 0) {
          $form_state->setErrorByName('url', "No valid smugmug source found for given url.");
        }
      }
      catch (ResourceException $e) {
        $form_state->setErrorByName('url', $e->getMessage());
      }
    }
  }
  
  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $this->processInputValues([['value'=>$form_state->getValue('url'), 'alt'=>$form_state->getValue('alt')]], $form, $form_state);
  }

}
