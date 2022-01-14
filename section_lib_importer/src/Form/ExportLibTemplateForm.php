<?php
namespace Drupal\section_lib_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ExportLibTemplateForm extends FormBase {
  
  function getFormId() {
    return 'section_lib_importer_export_form';
  }
  
  function buildForm(array $form, FormStateInterface $form_state) {
    $lib_templates = \Drupal::entityTypeManager()->getStorage('section_library_template')->loadMultiple(null);
    $templates = [];
    foreach ($lib_templates as $template) {
      $temp = [
        'label' => $template->label(),
        'type' => $template->type->value,
        'filename' => 'INSERT FILENAME'
      ];
      foreach ($template->layout_section as $layout) {
        $layout = $layout->getValue()['section'];
        $section = [
          'label' => $template->label(),
          'type' => $template->type->value,
          'section_id' => $layout->getLayoutId(),
          'section' => $layout->getLayoutSettings(),
          'blocks' => []
        ];

        foreach ($layout->getComponents() as $component) {
          $section['blocks'][] = [
            'region' => $component->getRegion(),
            
            'id' => $component->get('configuration')['id'],
            'label' => $component->get('configuration')['label'],
            'provider' => $component->get('configuration')['provider'],
            'label_display' => $component->get('configuration')['label_display'],
            'view_mode' => $component->get('configuration')['view_mode'],
          ];
          // Info and body from serialized block
          // Blocks created and saved in block library not stored with layout
          if (array_key_exists('block_serialized', $component->get('configuration'))) {
            $index = count($section['blocks'])-1;
            $section['blocks'][$index]['info'] = unserialize($component->get('configuration')['block_serialized'])->label();
            $section['blocks'][$index]['body'] = unserialize($component->get('configuration')['block_serialized'])->body->value;
          }
          \Drupal::logger('section_lib_importer')->notice(serialize(array_keys($component->get('configuration'))));
          \Drupal::logger('section_lib_importer')->notice(serialize($component->get('configuration')));
        }
        $temp['sections'][] = $section;
      }
      $templates[] = $temp;
    }
    $form = [
      'template_selector' => [
        '#type' => 'select',
        '#title' => "Select a Layout",
        '#options' => array_column($templates, 'label'),
        '#attributes' => array('onchange' => 'this.form.submit();'),
        '#default_value' => 0
      ],
      'export' => [
        '#type' => 'textarea',
        '#title' => 'Output',
        '#rows' => 16,
        '#value' => str_replace(['":"', '":null', '":{', '":[', '":0', '{', '}', '<\/'], ['" => "', '" => null', '" => [', '" => [', '" => 0', '[', ']', '</'], json_encode($templates[($form_state->getValue('template_selector') === null ? 0 : $form_state->getValue('template_selector'))])),
      ]
    ];

    return $form;
  }
  
  function submitForm(array &$form, FormStateInterface $form_state) {
    
  }
}
