<?php

namespace Drupal\drupal_caching\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Articles - Caching' custom block.
 *
 * @Block(
 *   id = "articles_custom_block",
 *   admin_label = @Translation("Articles - Caching")
 * )
 */
class ArticlesCustomBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a ArticlesCustomBlock object.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the latest 3 articles.
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'article_custom_block')
      ->sort('created', 'DESC')
      ->accessCheck(FALSE)
      ->range(0, 3);

    // Execute the query and load nodes.
    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);

    $rows = [];
    $cache_tags = [];

    // Process each node and build the table rows.
    foreach ($nodes as $node) {
      $node_id = $node->id();
      $title = $node->getTitle();
      $body = $node->get('body')->value ?? '';
      $body_text = strip_tags($body);
      $date = $node->hasField('field_date') ? $node->get('field_date')->value : '';

      // Add row for each article.
      $rows[] = [
        'data' => [$node_id, $title, $body_text, $date],
      ];

      // Merge cache tags in a single step.
      $cache_tags = Cache::mergeTags($cache_tags, $node->getCacheTags());
    }

    // Current user Email Address.
    $email = $this->currentUser->getEmail();

    // Add email as a separate row in the table.
    $rows[] = [
      'data' => ['Current User Email:', $email],
    ];

    // Return the rendered table.
    return [
      '#theme' => 'table',
      '#header' => ['Node ID', 'Title', 'Body', 'Date'],
      '#rows' => $rows,
      '#cache' => [
        'tags' => $cache_tags,
        'contexts' => ['user'],
      ],
    ];
  }
}
