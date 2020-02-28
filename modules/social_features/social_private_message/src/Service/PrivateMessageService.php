<?php

namespace Drupal\social_private_message\Service;

use Drupal\private_message\Service\PrivateMessageService as PrivateMessageServiceBase;

/**
 * Class PrivateMessageMapper.
 *
 * @package Drupal\social_private_message\Service
 */
class PrivateMessageService extends PrivateMessageServiceBase {

  /**
   * Creates the private message thread for the given members.
   *
   * @param \Drupal\user\UserInterface[] $members
   *   An array of User objects for whom the private message
   *   thread should be retrieved.
   *
   * @return \Drupal\private_message\Entity\PrivateMessageThread
   *   A private message thread that contains all members in the thread.
   */
  public function getNewThreadForMembers(array $members) {
    return $this->createPrivateMessageThread($members);
  }

}
