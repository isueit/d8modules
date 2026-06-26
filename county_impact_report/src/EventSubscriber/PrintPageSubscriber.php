<?php

namespace Drupal\county_impact_report\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Event subscriber for print page requests.
 */
class PrintPageSubscriber implements EventSubscriberInterface {

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new PrintPageSubscriber.
   */
  public function __construct(PathMatcherInterface $path_matcher, AliasManagerInterface $alias_manager) {
    $this->pathMatcher = $path_matcher;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    return $events;
  }

  /**
   * Handles the request event.
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $path = $request->getPathInfo();

    // Skip if already in /node/{id}/print format to avoid infinite redirects
    if (preg_match('#^/node/\d+/print$#', $path)) {
      return;
    }

    // Check if path ends with /print
    if (preg_match('#^(.+)/print$#', $path, $matches)) {
      $base_path = $matches[1];
      
      // Get the system path from the alias
      $system_path = $this->aliasManager->getPathByAlias($base_path);
      
      // Check if it's a node path and NOT already /node/{id}
      if (preg_match('#^/node/(\d+)$#', $system_path, $node_matches)) {
        $nid = $node_matches[1];
        
        // Extract the base URL prefix (e.g., /linn)
        $base_url = $request->getBaseUrl();
        
        // Build the print URL with the base path preserved
        $print_url = $base_url . '/node/' . $nid . '/print';
        $response = new RedirectResponse($print_url, 307);
        $event->setResponse($response);
      }
    }
  }

}