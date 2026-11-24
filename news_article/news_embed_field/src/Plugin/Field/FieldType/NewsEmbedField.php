<?php

namespace Drupal\news_embed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
#use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
#use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Plugin implementation of the news_embed_field field type.
 *
 * @FieldType(
 *   id = "news_embed_field",
 *   label = @Translation("News Embed"),
 *   module = "news_embed_field",
 *   description = @Translation("Stores a URL to a news article and then outputs the body of the article."),
 *   category = "general",
 *   default_widget = "news_embed_field_default",
 *   default_formatter = "news_embed_field_default",
 * )
 */

class NewsEmbedField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = array(
      'url' => array(
        'description' => 'The URL of news article to embed',
        'type' => 'varchar',
        'length' => '255',
        'not null' => FALSE,
      ),
      'local_info' => array(
        'description' => 'Local information about the article',
        'type' => 'text',
        'size' => 'medium',
        'not null' => FALSE,
      ),
    );

    $schema = array(
       'columns' => $columns,
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['url'] = DataDefinition::create('string')
      ->setLabel(t('Snippet description'));

    $properties['local_info'] = DataDefinition::create('string')
      ->setLabel(t('Snippet code'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('url')->getValue();
    return empty($value);
  }

}
