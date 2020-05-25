<?php
namespace Drupal\flip_clock\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Provides a Deposit Information block.
 *
 * @Block(
 *  id = "flip_clock",
 *  admin_label = @Translation("Flip Clock"),
 * )
 */
class FlipClockManager extends BlockBase implements ContainerFactoryPluginInterface
{
    /**
     * Constructs the top links in Program Details.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *   The container object.
     * @param array                                                     $configuration
     *   Contain the configuration.
     * @param string                                                    $plugin_id
     *   Contain the plugin id.
     * @param mixed                                                     $plugin_definition
     *   Contain the plugin_definition.
     *
     * @return static
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static($configuration, $plugin_id, $plugin_definition);
    }
    /**
     * Constructs the top links in Program Details.
     *
     * @param array  $configuration
     *   Contain configuration.
     * @param string $plugin_id
     *   Contain plugin_id.
     * @param mixed  $plugin_definition
     *   Contain plugin_definition.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }
    /**
     * {@inheritdoc}
     *
     * @return template
     */
    public function build()
    {
        $content['#cache']['max-age'] = 0;
        $content['info'] = [
        '#theme' => 'flip_clock',
        '#attached' => [
        'library' => ['flip_clock/flip_clock.block'],
        ],
        '#items' => ''
        ];
        return $content;
    }
}
