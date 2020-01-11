<?php

namespace Drupal\social_content_report;

use Drupal\comment\Entity\Comment;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\flag\FlagServiceInterface;
use Drupal\social_post\Entity\Post;

/**
 * Provides a content report service.
 */
class ContentReportService implements ContentReportServiceInterface {

  use StringTranslationTrait;

  /**
   * Flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor for ContentReportService.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   Flag service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    FlagServiceInterface $flag_service,
    AccountProxyInterface $current_user,
    ModuleHandlerInterface $module_handler
  ) {
    $this->flagService = $flag_service;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getReportFlagTypes(): array {
    $report_flags = $this->moduleHandler->invokeAll('social_content_report_flags');

    // Allow using reports for three predefined entity types.
    $report_flags = array_merge($report_flags, [
      'report_comment',
      'report_node',
      'report_post',
      'block_user',
    ]);

    $this->moduleHandler->alter('social_content_report_flags', $report_flags);

    return $report_flags;
  }

  /**
   * {@inheritdoc}
   */
  public function getModalLink(EntityInterface $entity, $flag_id, $is_button = FALSE): ?array {
    // Check if users may flag this entity.
    if (!$this->currentUser->hasPermission('flag ' . $flag_id)) {
      return NULL;
    }

    $flag = $this->flagService->getFlagById($flag_id);
    $flagging = $this->flagService->getFlagging($flag, $entity, $this->currentUser);

    // If the user already flagged this, we return a disabled link to nowhere.
    if ($flagging) {
      $element = [
        'title' => $this->t('Reported'),
        'attributes' => [
          'class' => [
            'disabled',
          ],
        ],
      ];

      if ($is_button) {
        $element += [
          'url' => Url::fromRoute('<none>'),
          'attributes' => [
            'class' => [
              'btn',
              'btn-link',
            ],
          ],
        ];
      }

      return $element;
    }

    // Return the modal link if the user did not yet flag this content.
    return [
      'title' => $this->t('Report'),
      'url' => Url::fromRoute('flag.field_entry',
        [
          'flag' => $flag_id,
          'entity_id' => $entity->id(),
        ],
        [
          'query' => [
            'destination' => Url::fromRoute('<current>')->toString(),
          ],
        ]
      ),
      'attributes' => [
        'data-dialog-type' => 'modal',
        'data-dialog-options' => JSON::encode([
          'width' => 400,
          'dialogClass' => 'content-reporting-dialog',
        ]),
        'class' => ['use-ajax', 'content-reporting-link'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getUserModalLink(EntityInterface $entity, $flag_id, $is_button = FALSE): ?array {
    // Check if users may flag this entity.
    if (!$this->currentUser->hasPermission('flag ' . $flag_id)) {
      return NULL;
    }
    if (!method_exists($entity, 'getOwner')) {
      return NULL;
    }

    $flag = $this->flagService->getFlagById($flag_id);
    /** @var \Drupal\user\Entity\User $owner */
    $owner = $entity->getOwner();
    $flagging = $this->flagService->getFlagging($flag, $owner, $this->currentUser);


    $route = 'flag.action_link_flag';
    $title = $this->t('Block the content from ' . $owner->getDisplayName());

    // If the user already flagged this, we can unblock him.
    if ($flagging) {
      $title = $this->t('Unblock the content from ' . $owner->getDisplayName());
      $route = 'flag.action_link_unflag';
    }

    // Return the modal link if the user did not yet flag this content.
    return [
      'title' => $title,
      'url' => Url::fromRoute($route,
        [
          'flag' => $flag_id,
          'entity_id' => $owner->id(),
        ],
        [
          'query' => [
            'destination' => Url::fromRoute('<current>')->toString(),
          ],
        ]
      ),
    ];
  }

  /**
   * This function will return Access based on the block_user flag.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity that was flagged.
   *
   * @return \Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   *   The access, forbidden if the user blocked the entity creater.
   */
  public function calculateEntityAccess(EntityInterface $entity) {
    // We do need the owner of the Entity
    // if it doesn't have any, we can't block it.
    if (!method_exists($entity, 'getOwner')) {
      return AccessResult::neutral();
    }
    /** @var \Drupal\user\Entity\User $owner */
    $owner = $entity->getOwner();
    $flag = $this->flagService->getFlagById('block_user');
    $flagging = $this->flagService->getFlagging($flag, $owner, $this->currentUser);

    // First check if there is a flag for block_user for the entity owner.
    // If not let's move on.
    if (!$flagging) {
      // No opinion.
      return AccessResult::neutral();
    }

    // The user flagged the Owner of the Entity as forbidden.
    // Reject access, so the content stays but is filtered out
    // everywhere.
    return AccessResult::forbidden();
  }

}
