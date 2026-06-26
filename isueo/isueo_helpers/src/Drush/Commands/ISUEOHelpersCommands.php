<?php

namespace Drupal\isueo_helpers\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class ISUEOHelpersCommands extends DrushCommands {

  /**
   * Constructs ISUEOHelpersCommands object.
   */
  /*public function __construct(
    private readonly Token $token,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  /*public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
    );
  }

  /**
   * Reindex the events into the collection
   */
  #[CLI\Command(name: 'isueo_helpers:typesense_information', aliases: ['typesense'])]
  #[CLI\Usage(name: 'isueo_helpers:typesense_information', description: 'Information on working with Typesense collections')]
  public function typesense_information($options = []) {
    $this->logger()->notice('Typesense: working with collections');
    $this->output()->writeln('');
    $this->output()->writeln(' Functions:');
    $this->output()->writeln('   - getClient(string $collection)');
    $this->output()->writeln('   - createCollections(string $collection)');
    $this->output()->writeln('   - truncateCollection(string $collection)');
    $this->output()->writeln('   - upsertSynonyms(string $collection)');
    $this->output()->writeln('   - searchCollection(string $collection, string $q = \'*\', string $query_by = \'*\', string $sort_by = \'\', int $per_page = 10, int $page = 1, string $filter_by = \'\', bool $exhaustive_search = false)');

    $this->output()->writeln('');
    $this->output()->writeln(' We don\'t have drush commands to create collections');
    $this->output()->writeln(' If the collection already exists, use the web interface to delete it prior to running the following drush command');
    $this->output()->writeln('');
    $this->output()->writeln('drush eval \'');
    $this->output()->writeln('  use Drupal\isueo_helpers\ISUEOHelpers\Typesense;');
    $this->output()->writeln('  Typesense::createCollection("plp_programs");');
    $this->output()->writeln('  plp_typesense_index_all_programs();');
    $this->output()->writeln('\'');
    $this->output()->writeln('');
  }
}
