<?php

namespace Drupal\drupal_caching\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that displays articles from the user's category field.
 *
 * @Block(
 *   id = "category_articles_block",
 *   admin_label = @Translation("Category Articles Block")
 * )
 */
class CategoryArticleBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new PreferredCategoryArticlesBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
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
    $uid = $this->currentUser->id();

    // Get the preferred category term ID from the current user.
    $user = \Drupal\user\Entity\User::load($uid);

    $build = [
      '#cache' => [
        'contexts' => ['user', 'preferred_category'],
      ],
    ];

    if ($user->hasField('field_categories_taxonomy')) {
      $category_tid = $user->get('field_categories_taxonomy');
      if (!empty($category_tid->getValue())) {
        $tid = $category_tid->getValue()[0]['target_id'];

        $query = \Drupal::entityQuery('node')
          ->condition('status', 1)
          ->condition('type', 'article')
          ->condition('field_categories_taxonomy.target_id', $tid)
          ->sort('created', 'DESC')
          ->accessCheck(FALSE)
          ->range(0, 3);

          $nids = $query->execute();
          $nodes = Node::loadMultiple($nids);

          $titles = [];
          foreach ($nodes as $node) {
              $titles[] = $node->getTitle();
          }

          $build['#theme'] = 'item_list';
          $build['#items'] = $titles;
      } else {
          $build['#markup'] = $this->t('User category field is empty.');
      }
    }

    return $build;
  }
}
