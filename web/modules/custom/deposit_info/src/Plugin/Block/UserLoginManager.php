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
*  id = "user_login_manager",
*  admin_label = @Translation("User Login Manager"),
* )
*/
class UserLoginManager extends BlockBase implements ContainerFactoryPluginInterface
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
    $loggedin_user_id = $this->currentUser->id();
    if($loggedin_user_id){
      $url = Url::fromUri('internal:/user/logout');
      $logout_link = [
        '#type' => 'link',
        '#title' => $this->t('Log Out'),
        '#url' => $url,
        '#attributes' => [
          'class' => ['nav-link'],
        ],
      ];
      $quick_links['logout'] = $logout_link;
      $image_markup = $this->getLinkImage('user-image.jpg', ' Edit User');
      $edit_user = Link::fromTextAndUrl($image_markup, Url::fromUri('internal:/user/'.$loggedin_user_id.'/edit', [
        'attributes' => [
          'class' => [
            'edit-user',
            'nav-link'
          ]
        ],
      ]))->toString();
      $quick_links['edit_user'] = $edit_user;
    }else{
      $url = Url::fromUri('internal:/user/login');
      $login_link = [
        '#type' => 'link',
        '#title' => $this->t('Log In'),
        '#url' => $url,
        '#attributes' => [
          'class' => ['nav-link'],
        ],
      ];
      $quick_links['login'] = $login_link;
    }
    $content['#cache']['max-age'] = 0;
    $content['info'] = [
      '#theme' => 'user_login_manager',
      '#items' => $quick_links
    ];
    return $content;
  }
  /**
   * To construct image.
   *
   * @param string $image
   *   It contains image name.
   * @param string $alt_title
   *   It contains alt and title value.
   *
   * @return string
   *   It contains image tag.
   */
  public function getLinkImage($image, $alt_title) {
    $img = [
      '#theme' => 'image',
      '#uri' => '/' . drupal_get_path('module', 'deposit_info') . '/images/' . $image,
      '#alt' => $alt_title,
      '#title' => $alt_title,
      '#width' => 30,
      '#height' => 30,
    ];
    $rendered_image = render($img);
    $image_markup = Markup::create($rendered_image);
    return $image_markup;
  }
}
