<?php

namespace Drupal\layout_builder_base\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'layout_builder_base.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_base_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['lbb_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Layout Builder Base Settings'),
    ];

    $form['default_values'] = [
      '#type' => 'details',
      '#title' => 'Default values',
      '#group' => 'lbb_settings',
      '#tree' => true,
    ];

    $form['override_options'] = [
      '#type' => 'details',
      '#title' => 'Override options',
      '#group' => 'lbb_settings',
      '#tree' => true,
    ];

    $form['default_values']['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Configuring the default settings here will apply to all the layouts. If you need custom logic per layout, you will have to create your own. Since the layouts can have any value for their options, you need to enter yourself the value of the selects.'),
    ];

    $form['default_values'] = array_merge($form['default_values'], $this->getDetails());

    $form['override_options']['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Those fields allow you to override the options that the layouts are proposing. The options should be written with the following format: class|Label with one option per line. You can also use the value &lt;none&gt; in case you want to completely remove this option from the interface. If you want to keep some options from the module, just add them to the list in addition of yours. Example: layout--background--white|White'),
    ];

    $form['override_options'] = array_merge($form['override_options'], $this->getDetails());

    $properties = $this->getProperties();

    foreach ($properties as $property => $infos) {
      $form['default_values'][$infos['group']][$property] = [
        '#type' => 'textfield',
        '#title' => $infos['label'],
        '#size' => 64,
        '#default_value' => $config->get($property),
      ];
    }

    foreach ($properties as $property => $infos) {
      $form['override_options'][$infos['group']][$property] = [
        '#type' => 'textarea',
        '#title' => $infos['label'],
        '#size' => 64,
        '#default_value' => $config->get(self::getOverrideConfigName($property)),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Function to get the name of the override config for a property.
   *
   * @param string $property
   *   The property key.
   *
   * @return string
   *   The override config name for a property.
   */
  public static function getOverrideConfigName($property) {
    return 'override_' . $property;
  }

  /**
   * Function to get the details used for the form.
   *
   * @return array
   *   The different details.
   */
  public function getDetails() {
    $details = [];

    $details['grid'] = [
      '#type' => 'details',
      '#title' => $this->t('Grid properties'),
    ];

    $details['background_detail'] = [
      '#type' => 'details',
      '#title' => $this->t('Background'),
    ];

    $details['margin'] = [
      '#type' => 'details',
      '#title' => $this->t('Margins'),
    ];

    $details['padding'] = [
      '#type' => 'details',
      '#title' => $this->t('Paddings'),
    ];

    $details['container'] = [
      '#type' => 'details',
      '#title' => $this->t('Container'),
    ];

    $details['styles'] = [
      '#type' => 'details',
      '#title' => $this->t('Styles'),
    ];

    return $details;
  }

  /**
   * Function to get the properties list.
   *
   * @return array
   *   The list of the properties.
   */
  public function getProperties() {
    return [
      'column_gap' => [
        'label' => $this->t('Column gap'),
        'group' => 'grid',
      ],
      'row_gap' => [
        'label' => $this->t('Row gap'),
        'group' => 'grid',
      ],
      'column_breakpoint' => [
        'label' => $this->t('Column breakpoint'),
        'group' => 'grid',
      ],
      'column_width' => [
        'label' => $this->t('Columns width'),
        'group' => 'grid',
      ],
      'two_column_width' => [
        'label' => $this->t('Two Columns width'),
        'group' => 'grid',
      ],
      'three_column_width' => [
        'label' => $this->t('Three Columns width'),
        'group' => 'grid',
      ],
      'align_items' => [
        'label' => $this->t('Align items'),
        'group' => 'grid',
      ],
      'customizable_columns' => [
        'label' => $this->t('Customizable columns'),
        'group' => 'grid',
      ],
      'background' => [
        'label' => $this->t('Background'),
        'group' => 'background_detail',
      ],
      'background_position' => [
        'label' => $this->t('Background Position'),
        'group' => 'background_detail',
      ],
      'background_attachment' => [
        'label' => $this->t('Background Attachment'),
        'group' => 'background_detail',
      ],
      'background_size' => [
        'label' => $this->t('Background Size'),
        'group' => 'background_detail',
      ],
      'background_overlay' => [
        'label' => $this->t('Background Overlay'),
        'group' => 'background_detail',
      ],
      'top_margin' => [
        'label' => $this->t('Top Margin'),
        'group' => 'margin',
      ],
      'bottom_margin' => [
        'label' => $this->t('Bottom Margin'),
        'group' => 'margin',
      ],
      'left_margin' => [
        'label' => $this->t('Left Margin'),
        'group' => 'margin',
      ],
      'right_margin' => [
        'label' => $this->t('Right Margin'),
        'group' => 'margin',
      ],
      'equal_top_bottom_margins' => [
        'label' => $this->t('Top Bottom Margins'),
        'group' => 'margin',
      ],
      'equal_left_right_margins' => [
        'label' => $this->t('Left Right Margins'),
        'group' => 'margin',
      ],
      'top_padding' => [
        'label' => $this->t('Top Padding'),
        'group' => 'padding',
      ],
      'bottom_padding' => [
        'label' => $this->t('Bottom Padding'),
        'group' => 'padding',
      ],
      'left_padding' => [
        'label' => $this->t('Left Padding'),
        'group' => 'padding',
      ],
      'right_padding' => [
        'label' => $this->t('Right Padding'),
        'group' => 'padding',
      ],
      'equal_top_bottom_paddings' => [
        'label' => $this->t('Top Bottom Paddings'),
        'group' => 'padding',
      ],
      'equal_left_right_paddings' => [
        'label' => $this->t('Left Right Paddings'),
        'group' => 'padding',
      ],
      'height' => [
        'label' => $this->t('Height'),
        'group' => 'container',
      ],
      'container' => [
        'label' => $this->t('Container'),
        'group' => 'container',
      ],
      'content_container' => [
        'label' => $this->t('Content Container'),
        'group' => 'container',
      ],
      'color' => [
        'label' => $this->t('Default color'),
        'group' => 'styles',
      ],
      'alignment' => [
        'label' => $this->t('Default alignment'),
        'group' => 'styles',
      ],
      'modifiers' => [
        'label' => $this->t('Modifiers'),
        'group' => 'styles',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $newconfig = $this->configFactory->getEditable(static::SETTINGS);

    $values = $form_state->getValues();

    $tabs = ['default_values', 'override_options'];

    $properties = $this->getProperties();

    foreach ($tabs as $tab) {
      foreach ($properties as $property => $infos) {
        $value = $values[$tab][$infos['group']][$property];
        $config_name = ($tab === 'default_values') ? $property : self::getOverrideConfigName($property);
        $newconfig->set($config_name, $value);
      }
    }

    $newconfig->save();
  }

}
