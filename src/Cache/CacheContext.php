<?php

namespace Drupal\drupal_caching\Cache;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

class CacheContext implements CacheContextInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new PreferredCategoryCacheContext object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("User's preferred category");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $uid = $this->currentUser->id();
    if ($uid) {
      $user = User::load($uid);
      if ($user && $user->hasField('field_categories_taxonomy')) {
        $term = $user->get('field_categories_taxonomy')->entity;
        if ($term instanceof Term) {
          return 'preferred_category:' . $term->id();
        }
      }
    }
    return 'preferred_category:none';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }
}
