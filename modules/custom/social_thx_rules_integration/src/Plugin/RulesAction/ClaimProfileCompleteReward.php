<?php

namespace Drupal\social_thx_rules_integration\Plugin\RulesAction;

use Drupal\Core\Queue\RequeueException;
use Drupal\rules\Core\RulesActionBase;
use GuzzleHttp\Exception\RequestException;

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
 *     "fetched_qr" = @ContextDefinition("string",
 *        label = @Translation("Base64 encoded QR image")
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
    $api_url = 'https://us-central1-thx-wallet-dev.cloudfunctions.net/api/rewards';

    $qr_body = '';
    try {
      $response = \Drupal::httpClient()->post($api_url, [
        'json' => [
          'pool' => $pool_address,
          'rule' => $rule_id
        ],
      ]);
      $qr_body = $response->getBody()->getContents();
      $prefix = 'data:image/png;base64,';

      if (substr($qr_body, 0, strlen($prefix)) == $prefix) {
        $qr_body = substr($qr_body, strlen($prefix));
      }
    }
    catch (RequestException $e) {
      return FALSE;
    }

    // Set the complete URL as a provided value for other actions.
    $this->setProvidedValue('fetched_qr', $qr_body);
  }

}
