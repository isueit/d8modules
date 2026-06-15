<?php
namespace Drupal\ts_events_collection\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "ts_events_collection_events_past",
 *   title = @Translation("Put past events into Typesense Collection"),
 *   cron = {"time" = 1}
 * )
 */
class EventsPast extends QueueWorkerBase {
  public function processItem($data) {
    // Your long-running logic here, one item at a time
    sleep(10);
    echo $data['Id'] . PHP_EOL;
    echo count($data) . PHP_EOL;
  }
}
