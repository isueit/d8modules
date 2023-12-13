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
          $raw_section = $component->toArray();
          //Remove UUID from block, it will be installation specific, search for it based on provider, id and label
          if (preg_match('/[a-z0-9_]+\:[a-z0-9]{8}\-[a-z0-9]{4}\-[a-z0-9]{4}\-[a-z0-9]{4}\-[a-z0-9]{12}/', $raw_section['configuration']['id'])) {
            $raw_section['configuration']['id'] = explode(':', $raw_section['configuration']['id'])[0];
          }
          
          //Include serialized blocks by base64 encoding them, prevents reading while in module, but can be converted using and base64 decoder, decoded when importing
          if (array_key_exists('block_serialized', $raw_section['configuration'])) {
            $raw_section['configuration']['block_serialized'] = base64_encode($raw_section['configuration']['block_serialized']);
          }
          
          $section['blocks'][] = $raw_section;
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
        '#value' => var_export($templates[($form_state->getValue('template_selector') === null ? 0 : $form_state->getValue('template_selector'))], true)
        //str_replace(['":"', '":null', '":{', '":[', '":0', '{', '}', '<\/'], ['" => "', '" => null', '" => [', '" => [', '" => 0', '[', ']', '</'], json_encode($templates[($form_state->getValue('template_selector') === null ? 0 : $form_state->getValue('template_selector'))])),
      ]
    ];

    return $form;
  }
  
  function submitForm(array &$form, FormStateInterface $form_state) {
    
  }
}
