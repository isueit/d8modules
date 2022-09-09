<?php
namespace Drupal\taxonomy_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\File\FileSystemInterface;


/**
 * Class ImportForm
 * @package Drupal\taxonomy_import\Form
 * @ingroup taxonomy_import
 */
class ImportForm extends FormBase {
  public function getFormId() {
    return 'taxonomy_import_settings';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('taxonomy_name');
    $vid = $form_state->getValue('machine_name');
    $desc = $form_state->getValue('description');
    if ($form_state->getValue('filename') != '') {
      $path = ImportForm::getFilePath($form_state->getValue('filename'));
    } else {
      $form_file = $form_state->getValue('file', 0);
      if (isset($form_file[0]) && !empty($form_file[0])) {
        $file = File::load($form_file[0]);
        $uri = $file->getFileUri();
        $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
        $path = $stream_wrapper_manager->realpath();
      }
    }
    if (!isset($path) && !isset($file)) {
      $path = NULL;
    }

    $vocabs = Vocabulary::loadMultiple();
    if (!isset($vocabs[$vid]) && !is_null($path)) {
      $vocab = Vocabulary::create(array(
        'vid' => $vid,
        'description' => $desc,
        'name' => $name,
      ));
      $vocab->save();

      \Drupal::messenger()->addMessage($this->t('The Taxonomy Vocabulary %vocab has been created.', ['%vocab' => $name]));
      ImportForm::loadVocabFromFile($path, $vid, $name);

    } else {
      \Drupal::messenger()->addMessage($this->t('The Taxonomy Vocabulary using the machine name %name, please choose another machine name.', ['%name' => $vid]));
    }
    if (isset($file) && $file) {
      $file->delete();
    }

  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['taxonomy_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Taxonomy Name'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    );
    $form['machine_name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine Name'),
      '#default_value' => '',
      '#max_length' => 255,
      '#machine_name' => array(
        'exists' => array(
          $this,
          'exists',
        ),
        'source' => array('taxonomy_name'),
      ),
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#size' => 60,
      '#maxlength' => 255,
    );
    $dir = DRUPAL_ROOT . '/' . drupal_get_path('module', 'taxonomy_import') . '/src/data/';
    $files = \Drupal::service('file_system')->scanDirectory($dir, '/\.(txt)/');
    $options = array('' => $this->t('Upload File Below'));
    foreach ($files as $file) {
      $options[$file->filename] = $file->filename;
    }
    $form['filename'] = array(
      '#type' => 'select',
      '#title' => t('Filename'),
      '#description' => t('Choose file from module or upload a file'),
      '#options' => $options,
    );

    $form['file'] = array(
      '#type' => 'managed_file',
      '#title' => t('File'),
      '#description' => t('Uploaded a file to generate taxonomy'),
      '#upload_location' => 'temporary://',
      '#upload_validators' => [
        'file_validate_extensions' => ['txt'],
        ],
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;
  }

  function validataForm(array &$form, FormStateInterface $form_state) {
    $path = getFilePath(trim($form_state->getValues('filename')));
    if (!file_exists($path)) {
      $form_state->setErrorByName('filename', t('Error: File not Found'));
    }
    $form_file = $form_state->getValue('file', 0);
    $file = $form_state->getValues('file');
    if (isset($form_file[0]) && !empty($form_file[0]) && $form_state->getValue('filename') == '') {
      $form_state->setErrorByName('filename', t('Error: Please select a file or choose one to upload'));
    }
  }

  function getFilePath($filename) {
    $path = $filename;
    if (substr($filename, 0, 1) != '/') {
      return DRUPAL_ROOT . '/' . drupal_get_path('module', 'taxonomy_import') . '/src/data/' . $filename;
    } else {
      return $filename;
    }
  }

  function loadVocabFromFile($path, $vid, $name) {
    if ($file = fopen($path, 'r')) {
      $count_added = 0;
      $count_skipped = 0;
      $count_blank = 0;
      $tids_array = array();
      while (!feof($file)) {
        $term = fgets($file);
        $tabs = strspn($term, " ")/2; #Assumes a two space tab
        $term = trim($term);
        if ($term != NULL && $term != "") {
          $create_arr = array(
            'vid' => $vid,
            'name' => $term,
          );
          if (!empty($tids_array)) {
            $last = array_keys($tids_array)[count($tids_array)-1];
            $last_tabs = $tids_array[$last];
            if ($tabs > $last_tabs) {
              $create_arr['parent'] = $last;
            } elseif (($tabs <= $last_tabs) && ($last_tabs != 0)) {
              while ($tids_array[array_keys($tids_array)[count($tids_array)-1]] >= $tabs) {
                array_pop($tids_array);
              }
              $last = array_keys($tids_array)[count($tids_array)-1];
              $create_arr['parent'] = $last;
            }
          }
          $term = Term::create($create_arr);
          $term->save();
          $tids_array[$term->id()] = $tabs;
          $count_added += 1;
        } elseif ($term != NULL && $term != "") {
          $count_skipped += 1;
        } elseif (!$term || $term == "") {
          $count_blank += 1;
        }
      }
      //Only use $this when in the form
      if (debug_backtrace()[1]['function'] == 'submitForm') {
        \Drupal::messenger()->addMessage($this->t('The Taxonomy Vocabulary %vocab added %added terms, skipping %skipped terms and %blank lines.', ['%vocab' => $name, '%added' => $count_added, '%skipped' => $count_skipped, '%blank' => $count_blank]));
      }
      fclose($file);
    }

  }

  /**
   * Allow any machine name, import more terms to existing
   */
  function exists($name) {
    return false;
  }
}
