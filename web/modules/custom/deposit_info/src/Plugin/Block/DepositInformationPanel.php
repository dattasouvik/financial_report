<?php
namespace Drupal\deposit_info\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\node\Entity\Node;
/**
* Provides a Deposit Information block.
*
* @Block(
*  id = "custom_deposit_information_panel",
*  admin_label = @Translation("Deposit Information Panel"),
* )
*/
class DepositInformationPanel extends BlockBase implements ContainerFactoryPluginInterface
{
  /**
   * The entity for user and node.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */

  protected $entityManager;
  /**
   * The logged in user entity.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */

  protected $currentUser;
  /**
   * The entity for current route information.
   *
   * @var \Drupal\Core\Session\RouteMatchInterface
   */

  protected $routeMatch;
  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */

  protected $entityQuery;
  /**
   * Constructs the top links in Program Details.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container object.
   * @param array $configuration
   *   Contain the configuration.
   * @param string $plugin_id
   *   Contain the plugin id.
   * @param mixed $plugin_definition
   *   Contain the plugin_definition.
   *
   * @return static
   */

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity.manager'), $container->get('current_user'), $container->get('current_route_match'), $container->get('entity.query'));
  }
  /**
   * Constructs the top links in Program Details.
   *
   * @param array $configuration
   *   Contain configuration.
   * @param string $plugin_id
   *   Contain plugin_id.
   * @param mixed $plugin_definition
   *   Contain plugin_definition.
   * @param Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Contain entity_manager.
   * @param Drupal\Core\Session\AccountProxy $current_user
   *   Contain current_user.
   * @param Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Contain route_match.
   */

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityManagerInterface $entity_manager,
    AccountProxy $current_user,
    RouteMatchInterface $route_match,
    QueryFactory $entity_query
  )
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->entityQuery = $entity_query;
  }
  /**
   * {@inheritdoc}
   */

  public function build()
  {
    $content['#cache']['max-age'] = 0;
    $deposit_info = $this->calculateDepositsData();
    $content['info'] = [
      '#theme' => 'deposit_information_panel',
      '#items' => $deposit_info
    ];
    return $content;
  }
  /*
  *Calculate Total Deposits
  *
  */
  public function calculateDepositsData() : array
  {
    $deposit_data = [];
	$loggedin_user_id = $this->currentUser->id();
    $query = $this->entityQuery->get('node');
    $group = $query
      ->andConditionGroup()
      ->condition('field_deposit_amount', 0, '>')
      ->condition('field_maturity_amount', 0, '>')
      ->condition('field_created_by', $loggedin_user_id , '=');
    $node_ids = $query->condition('status', 1)
      ->condition('field_status', 1 , '=')
      ->condition('type', 'deposit')
      ->condition($group)
      ->execute();
    if (count($node_ids)) {
      $deposit_amount = 0;
      $maturity_amount = 0;
      $deposit_node = $this->entityManager->getStorage('node')->loadMultiple($node_ids);
      foreach ($deposit_node as $deposit_node_data) {
		  // if($deposit_node_data->getOwner()->id() === $loggedin_user_id){
			  $deposit_amount += $deposit_node_data->field_deposit_amount->value;
			  $maturity_amount += $deposit_node_data->field_maturity_amount->value;
		  // }
      }
      $deposit_data['deposit_amount'] = $deposit_amount;
      $deposit_data['maturity_amount'] = $maturity_amount;
    }
    return $deposit_data;
  }
}
