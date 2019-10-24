<?php

namespace Drupal\social_advanced_image\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Plugin\FilterBase;

///**
// * Provides a filter to add copyright to inline images.
// *
// * @Filter(
// *   id = "filter_image_copyright",
// *   title = @Translation("Copyright Filter"),
// *   description = @Translation(Filter to add copyright to inline images"),
// *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
// * )
// */
class FilterCopyright extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['celebrate_invitation'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show Invitation?'),
      '#default_value' => $this->settings['celebrate_invitation'],
      '#description' => $this->t('Display a short invitation after the default text.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // TODO: Implement process() method.
  }

}
