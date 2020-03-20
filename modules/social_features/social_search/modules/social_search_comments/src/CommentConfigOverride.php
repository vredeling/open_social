<?php

namespace Drupal\social_search_comments;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class CommentConfigOverride.
 *
 * Adds comments to the search indexes.
 *
 * @package Drupal\social_search_comments
 */
class CommentConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Thankfully the changes for all indexes are the same to add comments.
    $indices = [
      'search_api.index.social_content',
      'search_api.index.social_all',
    ];
    foreach ($indices as $index) {
      if (in_array($index, $names)) {
        $overrides[$index] = [
          'dependencies' => [
            'modules' => [
              'comment' => 'comment',
            ],
            'config' => [
              'field.storage.comment.field_comment_body' => 'field.storage.comment.field_comment_body',
            ],
          ],
          'field_settings' => [
            'field_comment_body' => [
              'label' => 'Comment',
              'datasource_id' => 'entity:comment',
              'property_path' => 'field_comment_body',
              'type' => 'text',
              'dependencies' => [
                'config' => [
                  'field.storage.comment.field_comment_body',
                ],
              ],
            ],
            'status_1' => [
              'label' => 'status_1',
              'datasource_id' => 'entity:comment',
              'property_path' => 'status',
              'type' => 'boolean',
              'indexed_locked' => TRUE,
              'type_locked' => TRUE,
              'dependencies' => [
                'module' => [
                  'comment',
                ],
              ],
            ],
          ],
          'datasource_settings' => [
            'entity:comment' => [
              'bundles' => [
                'default' => TRUE,
                'selected' => [
                  'post_comment',
                ],
              ],
              'languages' => [
                'default' => TRUE,
                'selected' => [],
              ],
            ],
          ],
          // List fields as key => value because we're deep merging and not just
          // providing totally new arrays.
          'processor_settings' => [
            'ignorecase' => [
              'fields' => [
                'field_comment_body' => 'field_comment_body',
              ],
            ],
            'tokenizer' => [
              'fields' => [
                'field_comment_body' => 'field_comment_body',
              ],
            ],
            'transliteration' => [
              'fields' => [
                'field_comment_body' => 'field_comment_body',
              ],
            ],
          ],
        ];
      }
    }

    // Thankfully the changes for both views are the same.
    $views = [
      'views.view.search_all',
      'views.view.search_content',
    ];
    foreach ($views as $view) {
      if (in_array($view, $names)) {
        $overrides[$view] = [
          'display' => [
            'options' => [
              'view_modes' => [
                'entity:comment' => [
                  'comment' => 'teaser',
                  'post_comment' => 'teaser',
                ],
              ],
            ],
          ],
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialCommentConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
