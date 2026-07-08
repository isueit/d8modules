<?php

namespace Drupal\sf_fields\EventSubscriber;

use Drupal\salesforce\Event\SalesforceEvents;
use Drupal\salesforce_mapping\Event\SalesforceQueryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MySalesforceSubscriber implements EventSubscriberInterface
{

  public static function getSubscribedEvents()
  {
    return [
      SalesforceEvents::PULL_QUERY => 'pullQueryAlter',
    ];
  }

  public function pullQueryAlter(SalesforceQueryEvent $event)
  {
    $query = $event->getQuery();

    switch ($event->getMapping()->id()) {
      case 'mydata_leads':
        //$query->fields[] = 'Account.Name';
        //$query->fields[] = 'Account.BillingCity';
        $query->fields['Owner.Email'] = 'Owner.Email';
        $query->fields['Owner.FirstName'] = 'Owner.FirstName';
        $query->fields[] = 'Owner.Id';
        break;
      case 'test':
        $query->fields[] = 'Owner.LastName';
        break;
      default:
        break;
    }
  }
}
