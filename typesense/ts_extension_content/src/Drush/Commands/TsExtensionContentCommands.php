<?php

namespace Drupal\ts_extension_content\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class TsExtensionContentCommands extends DrushCommands {

  /**
   * Constructs a TsExtensionContentCommands object.
   */
  public function __construct(
    private readonly Token $token,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
    );
  }

  /**
   * Rebuild this site's pages in the collection.
   */
  #[CLI\Command(name: 'ts_extension_content:rebuild', aliases: [])]
  #[CLI\Usage(name: 'ts_extension_content:rebuild', description: 'Rebuild this site\'s pages in the collection')]
  public function rebuild($options = []) {
    ts_extension_content_index_all_nodes();
    $log_message = dt('Rebuilt nodes in the collection for this site');
    $this->logger()->success($log_message);
  }

  /**
   * Delete this site's pages from the collection.
   */
  #[CLI\Command(name: 'ts_extension_content:delete', aliases: [])]
  #[CLI\Argument(name: 'sitename', description: 'Site Name, should be the same as used by drush')]
  #[CLI\Usage(name: 'ts_extension_content:delete', description: 'Delete this site\'s nodes from the collection')]
  #[CLI\Usage(name: 'ts_extension_content:delete sitename', description: 'Delete the sitename\'s nodes from the collection')]
  public function delete($sitename='', $options = []) {
    ts_extension_content_delete_all_from_collection($sitename);
    $log_message = dt('Deleted') . (empty($sitename) ? ' ' : ' "' . $sitename . '" ') . dt('nodes from collection');
    $this->logger()->success($log_message);
  }

  /**
   * An example of the table output format.
   */
  /*
  #[CLI\Command(name: 'ts_extension_content:token', aliases: ['token'])]
  #[CLI\FieldLabels(labels: [
    'group' => 'Group',
    'token' => 'Token',
    'name' => 'Name'
  ])]
  #[CLI\DefaultTableFields(fields: ['group', 'token', 'name'])]
  #[CLI\FilterDefaultField(field: 'name')]
  public function token($options = ['format' => 'table']): RowsOfFields {
    $all = $this->token->getInfo();
    foreach ($all['tokens'] as $group => $tokens) {
      foreach ($tokens as $key => $token) {
        $rows[] = [
          'group' => $group,
          'token' => $key,
          'name' => $token['name'],
        ];
      }
    }
    return new RowsOfFields($rows);
  }
  */

}
