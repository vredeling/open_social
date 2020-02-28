<?php

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Claim Profile Complete Reward' action.
 *
 * @RulesAction(
 *   id = "rules_claim_profile_complete_reward",
 *   label = @Translation("Claim Profile Complete Reward"),
 *   category = @Translation("Open Social"),
 *   context = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity, which should be deleted permanently.")
 *     )
 *   }
 * )
 */
class ClaimProfileCompleteReward extends RulesActionBase {

  /**
   * Claims the reward that has been set in the
   * THX reward pool for completing the profile.
   */
  protected function doExecute() {
    // Trigger POST towards THX API.
  }

}
