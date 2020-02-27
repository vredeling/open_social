<?php

namespace Drupal\social_rules\Plugin\Condition;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Content created' condition.
 *
 * @Condition(
 *   id = "rules_content_created",
 *   label = @Translation("Amount of content created"),
 *   category = @Translation("Open Social"),
 *   context = {
 *     "content_amount" = @ContextDefinition("integer",
 *       label = @Translation("Amount of content")
 *     )
 *   }
 * )
 */
class ContentCreated extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * Database services.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Database services.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * Check if the user created the amount of content defined in the condition.
   *
   * @return bool
   *   TRUE if the amount of content has been reached.
   */
  protected function doEvaluate() {
    $current_user = \Drupal::currentUser()->id();

    $query = $this->database->select('node_field_data', 'n')
      ->condition('uid', $current_user);
    $nodes_created = $query->countQuery()->execute()->fetchField();

    $query = $this->database->select('post_field_data', 'p')
      ->condition('user_id', $current_user);
    $posts_created = $query->countQuery()->execute()->fetchField();

    $query = $this->database->select('groups_field_data', 'g')
      ->condition('uid', $current_user);
    $groups_created = $query->countQuery()->execute()->fetchField();

    $total_amount = $groups_created + $posts_created + $nodes_created;

    return $total_amount === (int) $this->getContextValue('content_amount');
  }

}
