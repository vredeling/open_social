<?php

namespace Drupal\social_album\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\social_album\Controller\SocialAlbumController;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access control to albums page.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "social_album",
 *   title = @Translation("Album")
 * )
 */
class SocialAlbumAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_custom_access', SocialAlbumController::class . '::checkAlbumsAccess');
  }

}
