<?php

namespace Drupal\social_content_report\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\social_content_report\ContentReportServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class FlagSubscriber.
 */
class FlagSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait, LoggerChannelTrait;

  /**
   * Whether to unpublish the entity immediately on reporting or not.
   *
   * @var bool
   */
  protected $unpublishImmediately;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheInvalidator;

  /**
   * The content report service.
   *
   * @var \Drupal\social_content_report\ContentReportServiceInterface
   */
  protected $socialContentReport;

  /**
   * Creates a DiffFormatter to render diffs in a table.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_invalidator
   *   The cache tags invalidator service.
   * @param \Drupal\social_content_report\ContentReportServiceInterface $social_content_report
   *   The content report service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger,
    CacheTagsInvalidatorInterface $cache_invalidator,
    ContentReportServiceInterface $social_content_report
  ) {
    $this->unpublishImmediately = $config_factory->get('social_content_report.settings')->get('unpublish_threshold');
    $this->messenger = $messenger;
    $this->cacheInvalidator = $cache_invalidator;
    $this->socialContentReport = $social_content_report;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['onFlag'];
    return $events;
  }

  /**
   * Listener for flagging events.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The event when something is flagged.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onFlag(FlaggingEvent $event) {
    $flagging = $event->getFlagging();
    // Retrieve the entity.
    $entity = $flagging->getFlaggable();
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    if (!in_array($flagging->getFlagId(), $this->socialContentReport->getReportFlagTypes())) {
      // we want to invalide caches anyhow due to issue in flag:
      // https://www.drupal.org/project/flag/issues/2568131
      $this->cacheInvalidator->invalidateTags([$entity_type . ':' . $entity_id]);
      return;
    }

    $invalidated = FALSE;

    // Do nothing unless we need to unpublish the entity immediately.
    if ($this->unpublishImmediately) {
      try {
        $entity->setPublished(FALSE);
        $entity->save();
        $invalidated = TRUE;
      }
      catch (EntityStorageException $exception) {
        $this->getLogger('social_content_report')
          ->error(t('@entity_type @entity_id could not be unpublished after a user reported it.', [
            '@entity_type' => $entity_type,
            '@entity_id' => $entity_id,
          ]));
      }
    }

    // In any case log that the report was submitted.
    $this->messenger->addMessage($this->t('Your report has been submitted.'));

    // Clear cache tags for entity to remove the Report link.
    if (!$invalidated) {
      $this->cacheInvalidator->invalidateTags([$entity_type . ':' . $entity_id]);
    }
  }

}
