<?php

namespace Drupal\staff_profile_secondary\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\node\Entity\Node;
use \Drupal\Core\Url;
// Based on
// https://api.drupal.org/api/drupal/core%21modules%21system%21tests%21modules%21tabledrag_test%21src%21Form%21TableDragTestForm.php/class/TableDragTestForm/9.2.x

/**
 * StaffProfileDragForm provides a drag and drop interface to reorder staff profiles
 * Fixes unwanted behavior found in views drag and drop interface where reordering causes all weights to be set ascending from the lowest possible index
 */
class StaffProfileDragForm extends FormBase {
  protected $state;
  
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('state'));
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'staff_profile_drag_form';
  }
  
  protected function buildStaffTable(array $rows = [], $table_id = 'tabledrag-staff-profile-table', $group_prefix = 'tabledrag-staff-profile') {
    $tabledrag = [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => "{$group_prefix}-weight",
      ],
    ];
    $table = [
      '#type' => 'table',
      '#header' => [
        [
          'data' => $this->t('Name'),
          'colspan' => 2,
        ],
        $this->t('Weight'),
      ],
      '#tabledrag' => $tabledrag,
      '#attributes' => [
        'id' => $table_id,
      ],
      // Custom version of tabledrag.js, keeps as many at zero as possible, moved items are given weights relative to position
      '#attached' => [
        'library' => [
          'staff_profile_secondary/staff_profile_secondary_tabledrag',
        ],
      ],
    ];

    //Set rows to staff_profile nodes
    $query = \Drupal::entityTypeManager()->getListBuilder('node')->getStorage()->getQuery();
    $query->condition('type', 'staff_profile');
    $query->condition('status', 1);
    $query->sort('field_staff_profile_sort_order', 'ASC');
    $query->sort('field_staff_profile_last_name', 'ASC');
    $query->sort('field_staff_profile_first_name', 'ASC');

    $nids = $query->execute();
    $rows = Node::loadMultiple($nids);
    
    foreach ($rows as $id => $staff_profile) {
      
      $row = [];
      $row += [
        'parent' => '',
        'weight' => $staff_profile->field_staff_profile_sort_order->value,
        'depth' => 0,
        'classes' => [],
        'draggable' => TRUE,
      ];
      if (!empty($row['draggable'])) {
        $row['classes'][] = 'draggable';
      }
      $table[$id] = [
        'title' => [
          'indentation' => [
            '#theme' => 'indentation',
            '#size' => 0,
          ],
          //'#plain_text' => $staff_profile->label(),
          '#type' => 'link',
          '#title' => $staff_profile->label(),
          '#url' => Url::fromRoute('entity.node.canonical', ['node' => $id])
        ],
        'id' => [
          '#type' => 'hidden',
          '#value' => $id,
          '#parents' => [
            'table',
            $id,
            'id',
          ],
          '#attributes' => [
            'class' => [
              "{$group_prefix}-id",
            ],
          ],
        ],
        '#attributes' => [
          'class' => $row['classes'],
        ],
      ];
      $table[$id]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $row['weight'],
        '#delta' => 20,
        '#parents' => [
          'table',
          $id,
          'weight',
        ],
        '#attributes' => [
          'class' => [
            "{$group_prefix}-weight",
          ],
        ],
      ];
    }
    return $table;
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['table'] = $this->buildStaffTable();
    $form['actions'] = $this->buildFormActions();
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
   public function submitForm(array &$form, FormStateInterface $form_state) {
     $operation = isset($form_state->getTriggeringElement()['#op']) ? $form_state->getTriggeringElement()['#op'] : 'save';
     switch ($operation) {
       case 'reset':
        $this->state->set('tabledrag-staff-profile-table', array_flip(range(1, 5)));
        break;
      default:
        $table = [];
        foreach ($form_state->getValue('table') as $row) {
          $table[$row['id']] = $row;
          
          $staff_profile =  Node::load($row['id']);
          $staff_profile->field_staff_profile_sort_order->value = $row['weight'];
          $staff_profile->save();
        }
        $this->state->set('tabledrag-staff-profile-table', $table);
        break;
      }
   }
   
   protected function buildFormActions() {
     return [
       '#type' => 'actions',
       'save' => [
         '#type' => 'submit',
         '#op' => 'save',
         '#value' => $this->t('Save'),
       ],
       'reset' => [
         '#type' => 'submit',
         '#op' => 'reset',
         '#value' => $this->t('Reset'),
       ],
     ];
   }
}
