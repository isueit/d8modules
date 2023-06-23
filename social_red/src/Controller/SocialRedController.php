<?php
namespace Drupal\social_red\Controller;

use Drupal\Core\Controller\ControllerBase;
class SocialRed extends ControllerBase {
  public function social_red() {
    return array (
      '#theme' => 'socialmediared',
      '#version' => '3.0',
    );
  }
}