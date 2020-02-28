<?php

namespace Drupal\social_rules\Plugin\RulesAction;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides "Send email" rules action.
 *
 * @RulesAction(
 *   id = "social_rules_send_email",
 *   label = @Translation("Send email"),
 *   category = @Translation("Open Social"),
 *   context = {
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       description = @Translation("The email's message body.")
 *     ),
 *     "Url" = @ContextDefinition("string",
 *       label = @Translation("Fetched QR code URL")
 *     ),
 *   }
 * )
 *
 */
class SendEmail extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The logger channel the action will write log messages to.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a SystemSendEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The alias storage service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger, MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('rules'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * Send a system email.
   */
  protected function doExecute() {
    $langcode = LanguageInterface::LANGCODE_SITE_DEFAULT;
    $reply = NULL;
    $qrcode = $this->getContextValue('Url');
    $message = $this->getContextValue('message');
    $qrcode = "<img src='data:image/png;base64, " . $qrcode . "'/>";
    $subject = t('You got tokens!');
    $params = [
      'subject' => $subject,
      'message' => $message . '<br/>' . $qrcode,
    ];
    // Set a unique key for this email.
    $key = 'rules_action_mail_' . $this->getPluginId();

    $recipient = User::load(\Drupal::currentUser()->id());

    $message = $this->mailManager->mail('social_rules', $key, $recipient->getEmail(), $langcode, $params, $reply);

    if ($message['result']) {
      $this->logger->notice('Successfully sent email to %recipient', ['%recipient' => $recipient]);
    }

  }

}
