<?php

namespace Drupal\social_profile\Plugin\Condition;
use Drupal\profile\Entity\Profile;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Profile is complete' condition.
 *
 * @Condition(
 *   id = "rules_profile_is_complete",
 *   label = @Translation("Profile is complete"),
 *   category = @Translation("Open Social"),
 *   context = {
 *     "node" = @ContextDefinition("entity:profile",
 *       label = @Translation("Profile")
 *     )
 *   }
 * )
 */
class ProfileIsComplete extends RulesConditionBase {

  /**
   * Check if the given node is sticky.
   *
   * @param \Drupal\profile\Entity\Profile
   *   The profile to check.
   *
   * @return bool
   *   TRUE if the profile is completely filled in.
   */
  protected function doEvaluate(Profile $profile) {
    $messenger = \Drupal::messenger();
    $fields = $profile->getFields();
    foreach ($fields as $field) {
      if (strpos($field->getName(), 'field_') !== FALSE && $field->getName() !== 'field_profile_profile_tag') {
        $value = $field->getValue();
        if (empty($value)) {
          return FALSE;
        }

      }
    }
    return TRUE;
  }

}
