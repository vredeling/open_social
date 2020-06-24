<?php

namespace Drupal\social_scroll\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_scroll\SocialScrollManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialScrollSettingsForm.
 *
 * @package Drupal\social_scroll\Form
 */
class SocialScrollSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  const CONFIG_NAME = 'social_scroll.settings';

  /**
   * The Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The SocialScrollManager manager.
   *
   * @var \Drupal\social_scroll\SocialScrollManager
   */
  protected $SocialScrollManager;

  /**
   * SocialScrollSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\social_scroll\SocialScrollManager $social_infinite_scroll_manager
   *   The SocialScrollManager manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler, SocialScrollManager $social_infinite_scroll_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
    $this->SocialScrollManager = $social_infinite_scroll_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @noinspection  PhpParamsInspection */
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('social_scroll.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_scroll_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $all_views = $this->configFactory->listAll('views');
    $blocked_views = $this->SocialScrollManager->getBlockedViews();
    $views = array_diff($all_views, $blocked_views);
    // Get the configuration file.
    $config = $this->config(self::CONFIG_NAME);

    $form['page_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Here\'s a list of views that have a "page" display.'),
    ];

    $form['settings']['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $this->t('Load More'),
      '#maxlength' => '255',
    ];

    $form['settings']['automatically_load_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically Load Content'),
      '#description' => $this->t('Automatically load subsequent pages as the user scrolls.'),
      '#default_value' => TRUE,
    ];

    $form['settings']['views'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Views'),
    ];

    $options = [];
    foreach ($views as $view) {
      $label = $this->configFactory->getEditable($view)->getOriginal('label');
      $changed_view_id = str_replace('.', '__', $view);

      if ($label) {
        $options[$changed_view_id] = $label;
      }
    }

    $form['settings']['views']['list'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => array_keys($this->SocialScrollManager->getEnabledViews()),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $views_list = $form_state->getValues()['list'];

    foreach ($views_list as $key => $value) {
      if (strpos($key, 'views__') === FALSE) {
        unset($views_list[$key]);
      }
    }

    $config->set('views_list', $views_list)
      ->set('button_text', $form_state->getValue('button_text'))
      ->set('automatically_load_content', $form_state->getValue('automatically_load_content'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
