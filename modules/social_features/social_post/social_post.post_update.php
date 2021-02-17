<?php

/**
 * @file
 * Executes an update which is intended to update data, like entities.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Site\Settings;

/**
 * Trigger clean up functions for orphaned posts.
 */
function social_post_post_update_remove_orphaned_posts() {
  $connection = Database::getConnection();

  // Inner select of all users for the WHERE clause.
  $user_query = $connection->select('users', 'u')
    ->fields('u', ['uid']);

  // Find the user ids of deleted users where posts were left behind.
  $result = Database::getConnection()
    ->select('post_field_data', 'p')
    ->fields('p', ['id'])
    ->condition('user_id', $user_query, 'NOT IN')
    ->execute()
    ->fetchAll();

  $pids = [];
  foreach ($result as $row) {
    $pids[] = $row->id;
  }

  \Drupal::logger('social_post')->info('Removing @count orphaned posts for deleted users', ['@count' => count($pids)]);

  $storage_handler = \Drupal::entityTypeManager()->getStorage('post');
  $entities = $storage_handler->loadMultiple($pids);
  $storage_handler->delete($entities);
}

/**
 * Grant permission to administer post entities to CM and SM.
 */
function social_post_post_update_8601_administer_post_permissions() {
  user_role_grant_permissions('contentmanager', ['administer post entities']);
  user_role_grant_permissions('sitemanager', ['administer post entities']);
}

/**
 * Set the view mode to use when shown in activities.
 */
function social_post_post_update_8801() {
  activity_creator_set_entity_view_mode('post', 'activity');
}

/**
 * Give access to creating posts of specific types.
 */
function social_post_post_update_8802(&$sandbox) {
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = \Drupal::entityQuery('user_role')
      ->condition('id', 'administrator', '<>')
      ->count()
      ->execute();

    $sandbox['processed'] = 0;
    $sandbox['limit'] = Settings::get('entity_update_batch_size', 50);
    $sandbox['permissions'] = array_keys(\Drupal::service('social_post.permission_generator')->permissions());
  }

  $role_ids = \Drupal::entityQuery('user_role')
    ->condition('id', 'administrator', '<>')
    ->range($sandbox['processed'], $sandbox['limit'])
    ->execute();

  $storage = \Drupal::entityTypeManager()->getStorage('user_role');

  foreach ($role_ids as $role_id) {
    /** @var \Drupal\user\RoleInterface $role */
    $role = $storage->load($role_id);

    if ($role->hasPermission('add post entities')) {
      user_role_grant_permissions($role_id, $sandbox['permissions']);
    }
  }

  $sandbox['processed'] += count($role_ids);

  $sandbox['#finished'] = $sandbox['processed'] / $sandbox['total'];
}

/**
 * Create "Featured" view mode/display for post.
 */
function social_post_post_update_8804() {
  // Create a new post featured entity view mode.
  if (!EntityViewMode::load('post.featured')) {
    EntityViewMode::create([
      'targetEntityType' => 'post',
      'id' => 'post.featured',
      'status' => TRUE,
      'label' => t('Featured'),
    ])->save();
  }

  // Create view display for post bundle of Post entity.
  if (!EntityViewDisplay::load('post.post.featured')) {
    $display = EntityViewDisplay::load('post.post.default')->toArray();
    unset(
      $display['content']['field_post_comments'],
      $display['hidden']['like_and_dislike']
    );
    $display['content']['like_and_dislike'] = [
      'weight' => 3,
      'region' => 'content',
    ];
    $display = array_merge($display, [
      'uuid' => NULL,
      '_core' => NULL,
      'targetEntityType' => 'post',
      'mode' => 'featured',
    ]);
    EntityViewDisplay::create($display)->save();
  }
}

/**
 * Update likes in post activity and comment view modes.
 */
function social_post_post_update_8901() {
  /** @var \Drupal\update_helper\Updater $updateHelper */
  $updateHelper = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updateHelper->executeUpdate('social_post', 'social_post_update_8901');

  // Output logged messages to related channel of update execution.
  return $updateHelper->logger()->output();
}
