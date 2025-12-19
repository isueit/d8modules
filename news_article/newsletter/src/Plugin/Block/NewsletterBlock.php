<?php

namespace Drupal\newsletter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Views;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'Newsletter' Block.
 *
 * @Block(
 *   id = "newsletter_archive_block",
 *   admin_label = @Translation("Newsletter Block"),
 *   category = @Translation("Newsletter"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
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
      'debug_mode' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // Check if user has permission to place newsletter blocks
    return AccessResult::allowedIfHasPermissions($account, [
      'place newsletter blocks',
      'administer blocks',
    ], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Load all newsletter type taxonomy terms
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadTree('newsletter', 0, NULL, TRUE);

    $options = [];
    foreach ($terms as $term) {
      $options[$term->id()] = $term->getName();
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

    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#description' => $this->t('Show debug information to troubleshoot the block.'),
      '#default_value' => $this->configuration['debug_mode'],
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
    $this->configuration['debug_mode'] = $form_state->getValue('debug_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $newsletter_type = $this->configuration['newsletter_type'];
    $view_display = $this->configuration['view_display'];
    $debug_mode = $this->configuration['debug_mode'] ?? FALSE;

    if (!$newsletter_type) {
      return [
        '#markup' => '<div class="newsletter-not-configured">' . $this->t('Please configure this block to select a newsletter type.') . '</div>',
      ];
    }

    // Try to load and execute the view
    $view = Views::getView('newsletter');
    
    if (!$view) {
      return [
        '#markup' => '<div class="newsletter-error">' . $this->t('Newsletter view not found.') . '</div>',
      ];
    }

    if (!$view->access($view_display)) {
      return [
        '#markup' => '<div class="newsletter-error">' . $this->t('No access to view display: @display', ['@display' => $view_display]) . '</div>',
      ];
    }

    $view->setDisplay($view_display);
    $view->setArguments([$newsletter_type]);
    $view->preExecute();
    $view->execute();

    // Debug information
    if ($debug_mode) {
      $debug_info = [
        '#markup' => '<div class="newsletter-debug" style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">
          <strong>Debug Info:</strong><br>
          Newsletter Type ID: ' . $newsletter_type . '<br>
          View Display: ' . $view_display . '<br>
          Results Count: ' . count($view->result) . '<br>
          View Executed: ' . ($view->executed ? 'Yes' : 'No') . '<br>
          Arguments: ' . print_r($view->args, TRUE) . '
        </div>',
      ];
      $build[] = $debug_info;
    }

    // Check if we have results
    if (!empty($view->result)) {
      $build[] = [
        '#type' => 'view',
        '#name' => 'newsletter',
        '#display_id' => $view_display,
        '#arguments' => [$newsletter_type],
        '#embed' => TRUE,
      ];
    }
    else {
      // No results - try to query directly to see if content exists
      if ($debug_mode) {
        $node_storage = $this->entityTypeManager->getStorage('node');
        
        // Query for nodes with this taxonomy term (adjust field name as needed)
        $query = $node_storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('type', 'newsletter') // Adjust content type machine name
          ->condition('status', 1)
          ->condition('field_newsletter_type', $newsletter_type); // Adjust field name
        
        $nids = $query->execute();
        
        $debug_info = [
          '#markup' => '<div class="newsletter-debug" style="background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffc107;">
            <strong>No Results Found</strong><br>
            Direct query found ' . count($nids) . ' nodes with this taxonomy term.<br>
            Node IDs: ' . implode(', ', $nids) . '<br>
            <em>If nodes exist, check your view\'s contextual filter configuration.</em>
          </div>',
        ];
        $build[] = $debug_info;
      }
      
      $build[] = [
        '#markup' => '<div class="newsletter-empty">' . $this->t('No newsletters available for this type.') . '</div>',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return array_merge(
      parent::getCacheContexts(),
      ['url.path']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    
    if ($newsletter_type = $this->configuration['newsletter_type']) {
      $cache_tags[] = 'taxonomy_term:' . $newsletter_type;
    }
    
    $cache_tags[] = 'config:views.view.newsletter';
    $cache_tags[] = 'taxonomy_term_list:newsletter';
    
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return parent::getCacheMaxAge();
  }

}