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
   * Check if the given profile is filled in completely.
   *
   * @param \Drupal\profile\Entity\Profile $profile
   *   The profile to check.
   *
   * @return bool
   *   TRUE if the profile is completely filled in.
   */
  protected function doEvaluate(Profile $profile) {
    // Get all the profile fields.
    $fields = $profile->getFields();
    foreach ($fields as $field) {
      // If the field starts with "field_" and is not profile_tag, we continue.
      if (strpos($field->getName(), 'field_') !== FALSE &&
        $field->getName() !== 'field_profile_profile_tag') {
        // If one of the fields is empty, we return FALSE.
        if (empty($field->getValue())) {
          return FALSE;
        }
      }
    }
    // None of the fields is empty, return TRUE.
    return TRUE;
  }

}
