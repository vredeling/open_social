<?php

namespace Drupal\social_private_message\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\user\Entity\User;

/**
 * Provides a 'Send Private Message' action.
 *
 * @RulesAction(
 *   id = "rules_send_private_message",
 *   label = @Translation("Send private message"),
 *   category = @Translation("Open Social"),
 *   context = {
 *     "userid" = @ContextDefinition("integer",
 *       label = @Translation("The user ID"),
 *       description = @Translation("Which User ID should be the sender")
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message content"),
 *       description = @Translation("The private message content to send to the user")
 *     ),
 *   }
 * )
 */
class SendPrivateMessage extends RulesActionBase {

  /**
   * Send a private message.
   */
  protected function doExecute() {
    $recipients = [];
    $sender = User::load($this->getContextValue('userid'));
    $recipients[] = $sender;

    $receiver = User::load(\Drupal::currentUser()->id());
    $recipients[] = $receiver;

    /** @var \Drupal\social_private_message\Service\PrivateMessageService $private_message_service */
    $private_message_service = \Drupal::service('private_message.service');

    // Create a pm thread between these users.
    // Lock it so nobody can change it.
    // This means a new Thread is always created even if it exists,
    // and ensure nobody can reply. So it's more like a DM.
    $thread = $private_message_service->getNewThreadForMembers($recipients);

    // Get body of pm.
    $private_message_body = check_markup($this->getContextValue('message'), 'full_html');

    // Create a single message with the pm body.
    $private_message = \Drupal::entityTypeManager()->getStorage('private_message')->create([
      'owner' => $sender,
      'message' => $private_message_body,
    ]);

    $private_message->save();
    if ($thread->hasField('field_locked')) {
      $thread->set('field_locked', TRUE);
    }
    $thread->addMessage($private_message)->save();

    // There is a contrib private message bug that when creating a new thread
    // and adding messages to it, for the recipient the $last_message and
    // $thread_last_check get the same timestamp. Showing no new messages badge.
    // https://www.drupal.org/project/private_message/issues/3043898
    // TODO:: Update to the correct version when issue has been solved.
    /** @var \Drupal\user\UserDataInterface $userData */
    $userData = \Drupal::service('user.data');
    $userData->set('private_message', $receiver->id(), 'private_message_thread:' . $thread->id(), 0);
  }

}
