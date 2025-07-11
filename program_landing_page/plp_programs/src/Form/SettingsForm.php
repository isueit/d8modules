<?php

namespace Drupal\plp_programs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'plp_programs.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('plp_programs.settings');

    $form['counties'] = array(
      '#type' => 'details',
      '#title' => t('Counties'),
      '#open' => false,
    );

    $form['counties']['county_boost'] = [
      '#type' => 'number',
      '#title' => $this->t('Boost for any number of counties'),
      '#description' => $this->t('If the program has any counties, it will get this boost (one time).'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('counties.county_boost')) ? '0' : $config->get('counties.county_boost'),
    ];
    $form['counties']['county_multiplier'] = [
      '#type' => 'number',
      '#title' => $this->t('Multiplier for counties'),
      '#description' => $this->t('.'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('counties.county_multiplier')) ? '0' : $config->get('counties.county_multiplier'),
    ];
    $form['counties']['county_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval for counties'),
      '#description' => $this->t(''),
      '#min' => 1,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('counties.county_interval')) ? '1' : $config->get('counties.county_interval'),
    ];

    $form['indicators'] = array(
      '#type' => 'details',
      '#title' => t('Indicators'),
      '#open' => false,
    );

    $form['indicators']['indicator_boost'] = [
      '#type' => 'number',
      '#title' => $this->t('Boost for any number of indicators'),
      '#description' => $this->t('If the program has any indicators, it will get this boost (one time).'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('indicators.indicator_boost')) ? '0' : $config->get('indicators.indicator_boost'),
    ];
    $form['indicators']['indicator_multiplier'] = [
      '#type' => 'number',
      '#title' => $this->t('Multiplier for indicators'),
      '#description' => $this->t('.'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('indicators.indicator_multiplier')) ? '0' : $config->get('indicators.indicator_multiplier'),
    ];
    $form['indicators']['indicator_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval for indicators'),
      '#description' => $this->t(''),
      '#min' => 1,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('indicators.indicator_interval')) ? '1' : $config->get('indicators.indicator_interval'),
    ];

    $form['measurements'] = array(
      '#type' => 'details',
      '#title' => t('Measurements'),
      '#open' => false,
    );

    $form['measurements']['measurement_boost'] = [
      '#type' => 'number',
      '#title' => $this->t('Boost for any number of measurements'),
      '#description' => $this->t('If the program has any measurements, it will get this boost (one time).'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('measurements.measurement_boost')) ? '0' : $config->get('measurements.measurement_boost'),
    ];
    $form['measurements']['measurement_multiplier'] = [
      '#type' => 'number',
      '#title' => $this->t('Multiplier for measurements'),
      '#description' => $this->t('.'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('measurements.measurement_multiplier')) ? '0' : $config->get('measurements.measurement_multiplier'),
    ];
    $form['measurements']['measurement_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval for measurements'),
      '#description' => $this->t(''),
      '#min' => 1,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('measurements.measurement_interval')) ? '1' : $config->get('measurements.measurement_interval'),
    ];

    $form['events'] = array(
      '#type' => 'details',
      '#title' => t('Events'),
      '#open' => false,
    );

    $form['events']['event_boost'] = [
      '#type' => 'number',
      '#title' => $this->t('Boost for any number of events'),
      '#description' => $this->t('If the program has any events, it will get this boost (one time).'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('events.event_boost')) ? '0' : $config->get('events.event_boost'),
    ];
    $form['events']['event_multiplier'] = [
      '#type' => 'number',
      '#title' => $this->t('Multiplier for events'),
      '#description' => $this->t('.'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('events.event_multiplier')) ? '0' : $config->get('events.event_multiplier'),
    ];
    $form['events']['event_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval for events'),
      '#description' => $this->t(''),
      '#min' => 1,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('events.event_interval')) ? '1' : $config->get('events.event_interval'),
    ];

    $form['children'] = array(
      '#type' => 'details',
      '#title' => t('Children'),
      '#open' => false,
    );

    $form['children']['child_boost'] = [
      '#type' => 'number',
      '#title' => $this->t('Boost for any number of children'),
      '#description' => $this->t('If the program has any children, it will get this boost (one time).'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('children.child_boost')) ? '0' : $config->get('children.child_boost'),
    ];
    $form['children']['child_multiplier'] = [
      '#type' => 'number',
      '#title' => $this->t('Multiplier for children'),
      '#description' => $this->t('.'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('children.child_multiplier')) ? '0' : $config->get('children.child_multiplier'),
    ];
    $form['children']['child_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval for children'),
      '#description' => $this->t(''),
      '#min' => 1,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('children.child_interval')) ? '1' : $config->get('children.child_interval'),
    ];

    $form['courses'] = array(
      '#type' => 'details',
      '#title' => t('Courses'),
      '#open' => false,
    );

    $form['courses']['course_boost'] = [
      '#type' => 'number',
      '#title' => $this->t('Boost for any number of courses'),
      '#description' => $this->t('If the program has any courses, it will get this boost (one time).'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('courses.course_boost')) ? '500' : $config->get('courses.course_boost'),
    ];
    $form['courses']['course_multiplier'] = [
      '#type' => 'number',
      '#title' => $this->t('Multiplier for courses'),
      '#description' => $this->t('.'),
      '#min' => 0,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('courses.course_multiplier')) ? '0' : $config->get('courses.course_multiplier'),
    ];
    $form['courses']['course_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval for courses'),
      '#description' => $this->t(''),
      '#min' => 1,
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => empty($config->get('courses.course_interval')) ? '1' : $config->get('courses.course_interval'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitForm($form, $form_state);

    $this->config('plp_programs.settings')
      ->set('counties.county_boost', $form_state->getValue('county_boost'))
      ->set('counties.county_multiplier', $form_state->getValue('county_multiplier'))
      ->set('counties.county_interval', $form_state->getValue('county_interval'))

      ->set('indicators.indicator_boost', $form_state->getValue('indicator_boost'))
      ->set('indicators.indicator_multiplier', $form_state->getValue('indicator_multiplier'))
      ->set('indicators.indicator_interval', $form_state->getValue('indicator_interval'))

      ->set('measurements.measurement_boost', $form_state->getValue('measurement_boost'))
      ->set('measurements.measurement_multiplier', $form_state->getValue('measurement_multiplier'))
      ->set('measurements.measurement_interval', $form_state->getValue('measurement_interval'))

      ->set('events.event_boost', $form_state->getValue('event_boost'))
      ->set('events.event_multiplier', $form_state->getValue('event_multiplier'))
      ->set('events.event_interval', $form_state->getValue('event_interval'))

      ->set('children.child_boost', $form_state->getValue('child_boost'))
      ->set('children.child_multiplier', $form_state->getValue('child_multiplier'))
      ->set('children.child_interval', $form_state->getValue('child_interval'))

      ->set('courses.course_boost', $form_state->getValue('course_boost'))
      ->set('courses.course_multiplier', $form_state->getValue('course_multiplier'))
      ->set('courses.course_interval', $form_state->getValue('course_interval'))

      ->save();

      plp_programs_getprograms();
  }
}
