<?php
namespace Drupal\section_lib_importer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\section_lib_importer\Form\ExportLibTemplateForm;

/**
 * Export for template
 */ 
 class ExportLibTemplateController extends ControllerBase {
   
   /**
    * Return renderable array
   */
   public function content() {
     
     $build = \Drupal::formBuilder()->getForm('Drupal\section_lib_importer\Form\ExportLibTemplateForm');
     return $build;
   }
 }
