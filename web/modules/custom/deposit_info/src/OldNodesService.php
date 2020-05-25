<?php
namespace Drupal\deposit_info;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Datetime\DrupalDateTime;
class OldNodesService
{
    protected $entityTypeManager;
    protected $entityQuery;
    public function __construct(EntityTypeManager $entity_type_manager, QueryFactory $entity_query)
    {
        $this->entityTypeManager = $entity_type_manager;
        $this->entityQuery = $entity_query;
    }
    public function load()
    {
      $now = new DrupalDateTime('now');
      $query = $this->entityQuery->get('node');
      $group = $query->orConditionGroup()
                  ->condition('field_maturity_date', $now->format(DATETIME_DATETIME_STORAGE_FORMAT) , '<')
                  ->condition('field_status', 0, '=');
      $nids = $query->condition('type', 'deposit')
                    ->condition('status', 1)
                    ->condition($group)
                    ->execute();
      $storage = $this->entityTypeManager->getStorage('node');
      if(count($nids)){
        return $storage->loadMultiple($nids);
      }
      return [];
    }
}
