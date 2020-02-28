<?php

namespace Drupal\social_thx_rules_integration\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Claim Profile Complete Reward' action.
 *
 * @RulesAction(
 *   id = "rules_claim_profile_complete_reward",
 *   label = @Translation("Claim Profile Complete Reward"),
 *   category = @Translation("Open Social"),
 *   context = {
 *     "rule_id" = @ContextDefinition("integer",
 *       label = @Translation("Rule ID"),
 *       description = @Translation("Specifies the rule ID for which we reward the user."),
 *       default_value = 0,
 *     )
 *   },
 *   provides = {
 *     "fetched_qr_url" = @ContextDefinition("string",
 *        label = @Translation("URL containing a QR code")
 *      ),
 *   }
 * )
 */
class ClaimProfileCompleteReward extends RulesActionBase {
  /**
   * Claims the reward that has been set in the
   * THX reward pool for completing the profile.
   */
  protected function doExecute() {
    // Get the pool address from the config.
    $pool_address = \Drupal::configFactory()->getEditable('social_thx_rules_integration.settings')->get('pool_address');
    // Get the rule ID that has been provided by the SM.
    $rule_id = $this->getContextValue('rule_id');
    // Lets build the URL for the QR image.
    $qr_url = 'https://us-central1-thx-wallet-dev.cloudfunctions.net/api/qr/claim/';
    $qr_url .= $pool_address;
    $qr_url .= '/' . $rule_id;
    // Set the complete URL as a provided value for other actions.
    $this->setProvidedValue('fetched_qr_url', $qr_url);
  }

}
