<?php

namespace Drupal\newsletter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Views;

/**
 * Provides a 'Newsletter' Block.
 *
 * @Block(
 *   id = "newsletter_archive_block",
 *   admin_label = @Translation("Newsletter Block"),
 *   category = @Translation("Newsletter"),
 * )
 */
class NewsletterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new NewsletterBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'newsletter_type' => NULL,
      'view_display' => 'block_1',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Load all newsletter type taxonomy terms
    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadTree('newsletter');

    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['newsletter_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Newsletter Type'),
      '#description' => $this->t('Select which newsletter type to display.'),
      '#options' => $options,
      '#default_value' => $this->configuration['newsletter_type'],
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select Newsletter Type -'),
    ];

    $form['view_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display Type'),
      '#description' => $this->t('Choose whether to show featured or archive content.'),
      '#options' => [
        'block_1' => $this->t('Featured'),
        'block_2' => $this->t('Archive'),
      ],
      '#default_value' => $this->configuration['view_display'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['newsletter_type'] = $form_state->getValue('newsletter_type');
    $this->configuration['view_display'] = $form_state->getValue('view_display');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $newsletter_type = $this->configuration['newsletter_type'];
    $view_display = $this->configuration['view_display'];

    if ($newsletter_type) {
      // Embed the view with the newsletter type as a contextual filter argument
      $view = Views::getView('newsletter');
      
      if ($view) {
        $view->setDisplay($view_display);
        $view->setArguments([$newsletter_type]);
        $view->execute();

        $build = [
          '#type' => 'view',
          '#name' => 'newsletter',
          '#display_id' => $view_display,
          '#arguments' => [$newsletter_type],
          '#embed' => TRUE,
        ];
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    
    // Add newsletter type term cache tag
    if ($newsletter_type = $this->configuration['newsletter_type']) {
      $cache_tags[] = 'taxonomy_term:' . $newsletter_type;
    }
    
    return $cache_tags;
  }

}