<?php
namespace Drupal\deposit_info\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Session\AccountProxy;
/**
* Controller routines for user routes.
*/
class DepositInfoDownload extends ControllerBase {
  /**
   * The node information.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;
  /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
  protected $entityTypeManager;
  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;
  /**
     * The date formatter service.
     *
     * @var \Drupal\Core\Datetime\DateFormatterInterface
     */
  protected $dateFormatter;
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;   
  /**
   * Constructs a new ProgramUser.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The EntityManagerInterface.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current account details service.   
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, QueryFactory $entity_query, DateFormatterInterface $date_formatter, AccountProxy $currentUser) {
    $this->entityTypeManager = $entity_manager;
    $this->entityQuery = $entity_query;
    $this->dateFormatter = $date_formatter;
	$this->currentUser = $currentUser;	
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
     $container->get('entity.manager'),$container->get('entity.query'),$container->get('date.formatter'),$container->get('current_user')
    );
  }
  /**
   * Redirects to the user password reset form.
   *
   * In order to never disclose a reset link via a referrer header this
   * controller must always return a redirect response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param Drupal\node\NodeInterface $node
   *   The node information.
   */
  public function depositDownload(Request $request = NULL) {
    $deposit_nodes_arr = $this->getDepositNodes();
    $rows = $data = $content = NULL;
    $data = ['Account Number','Deposit Amount (Rs)', 'Maturity Deposit (Rs)', 'Deposit Date (d/m/Y)', 'Maturity Date (d/m/Y)', 'R.O.I(%)', 'Deposit Info', 'Deposit Type', 'Duration','Status','Annual R.O.I (%)'];
    $rows[] = implode(',', $data);
    if (!empty($deposit_nodes_arr)) {
      $deposit_node = $this->entityTypeManager->getStorage('node')->loadMultiple($deposit_nodes_arr);
      if (!empty($deposit_node)) {
        foreach ($deposit_node as $deposit_node_data) {
            $data = NULL;
            $deposit_msg = '';
            $status_msg = $deposit_node_data->field_status->value ? ($deposit_node_data->field_status->value === '1' ? "Open": 'Closed'):'NA';
            switch($deposit_node_data->field_deposit_methods->value){
              case 'post':
                $deposit_msg = "Post OFfice";
                break;
              case 'bank':
                $deposit_msg = "Bank ".($deposit_node_data->field_bank_name->value);
                break;
              default:
                $deposit_msg = "NA";
            }
            $data = [
              $deposit_node_data->field_account_number->value,
              $deposit_node_data->field_deposit_amount->value,
              $deposit_node_data->field_maturity_amount->value,
              $this->dateFormatter->format(strtotime($deposit_node_data->field_deposit_date->value), 'custom', 'd/m/Y'),
              $this->dateFormatter->format(strtotime($deposit_node_data->field_maturity_date->value), 'custom', 'd/m/Y'),
              $deposit_node_data->field_rate_of_interest->value,
              $deposit_msg,
              $deposit_node_data->field_deposit_type->value,
              $deposit_node_data->field_duration->value,
              $status_msg,
              $deposit_node_data->field_annual_interest_rate->value,
            ];
            $rows[] = implode(',', $data);
        }
        $content = implode("\n", $rows);
      }
    }
    $response = new Response($content);
    $response->headers->set('Content-Type', 'text/csv', 'charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="deposit.csv"');
    return $response;
  }
  /*
  *Collect all Node of Type Deposit
  */
  public function getDepositNodes(): array{
    $node_ids = [];
	$loggedin_user_id = $this->currentUser->id();
    $query = $this->entityQuery->get('node');
    $node_ids = $query->condition('status', 1)
      ->condition('field_status', 1)
      ->condition('type', 'deposit')
	  ->condition('field_created_by', $loggedin_user_id , '=')
      ->execute();
    if (count($node_ids)) {
      return $node_ids;
    }
    return $node_ids;
  }
}
