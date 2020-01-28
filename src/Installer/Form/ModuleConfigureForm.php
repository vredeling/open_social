<?php

namespace Drupal\social\Installer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the site configuration form.
 */
class ModuleConfigureForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_module_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Install optional modules');

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('All the required modules and configuration will be automatically installed and imported. You can optionally select additional features.'),
    ];

    $form['install_modules'] = [
      '#type' => 'container',
    ];

    $form['install_modules']['title'] = [
      '#type' => 'item',
      '#markup' => '<strong>' . $this->t('Enable additional features') . '</strong>',
    ];

    // Checkboxes to enable Optional modules.
    foreach ($this->getOptionalModules() as $name => $checkbox) {
      $form['install_modules']['optional_module_' . $name] = [
        '#type' => 'checkbox',
      ] + $checkbox;
    }

    $form['install_demo'] = [
      '#type' => 'container',
    ];

    $form['install_demo']['title'] = [
      '#type' => 'item',
      '#markup' => '<strong>' . $this->t('Install demo content') . '</strong>',
    ];

    $form['install_demo']['explanation'] = [
      '#type' => 'item',
      '#markup' => $this->t('If this is your first time using Open Social we recommend you generate demo content to show you what Open Social can do. This demo content can be deleted in the platform later.'),
    ];

    $form['install_demo']['demo_content'] = [
      '#type' => 'checkbox',
      '#title' => t('Generate demo content and users'),
      '#description' => t('Will generate files, users, groups, events, topics, comments and posts.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $optional_modules = [];

    // Check which optional modules are checked.
    foreach ($this->getOptionalModules() as $key => $checkbox) {
      if ($form_state->getValue('optional_module_' . $key)) {
        $optional_modules[] = $key;
      }
    }

    // Set the modules to be installed by Drupal in the install_profile_modules
    // step.
    $install_modules = array_merge(
      \Drupal::state()->get('install_profile_modules'),
      $optional_modules
    );
    \Drupal::state()->set('install_profile_modules', $install_modules);

    // Store whether we need to set up demo content.
    \Drupal::state()->set('social_install_demo_content', $form_state->getValue('demo_content'));
  }

  /**
   * Contains the optional modules for Open Social.
   *
   * TODO: Refactor this into an OptionalModuleManagerService as used by
   *   Thunder.
   *
   * @return array
   *   The optional modules that users can install.
   */
  private function getOptionalModules() {
    return [
      'social_book' => [
        '#title' => $this->t('Book content type'),
        '#description' => $this->t('Allows you to organise content in a book like structure.'),
      ],
      'social_sharing' => [
        '#title' => $this->t('Social sharing'),
        '#description' => $this->t('Will add share links for social media sites to public content.'),
      ],
      'social_event_type' => [
        '#title' => $this->t('Event types'),
        '#description' => $this->t('Allows events to be organised by event types.'),
      ],
      'social_sso' => [
        '#title' => $this->t('Social Registration'),
        '#description' => $this->t('Provides users with the option to register or login using social media sites.'),
      ],
      'social_search_autocomplete' => [
        '#title' => $this->t('Interactive search suggestions'),
        '#description' => $this->t('Provides real-time search results in the search overlay.'),
        '#default_value' => TRUE,
      ],
      'social_file_private' => [
        '#title' => $this->t('Private file system (recommended)'),
        '#description' => $this->t('Use the private file system for uploaded files.'),
        '#default_value' => TRUE,
      ],
      'inline_form_errors' => [
        '#title' => $this->t('Inline Form Errors'),
        '#description' => $this->t('Shows errors in forms next to the incorrect field instead of at the top of the form.'),
        '#default_value' => TRUE,
      ],
      'page_cache' => [
        '#title' => $this->t('Anonymous page cache (recommended)'),
        '#description' => $this->t('Cache page for anonymous users. Enable this if you do not have a web server to do this for you.'),
        '#default_value' => TRUE,
      ],
      'dynamic_page_cache' => [
        '#title' => $this->t('Dynamic page cache (recommended)'),
        '#description' => $this->t('Caches parts of the page dynamically for any user'),
        '#default_value' => TRUE,
      ],
      'social_lets_connect_contact' => [
        '#title' => $this->t('Open Social help links'),
        '#description' => $this->t('Adds Open Social Links to the main menu.'),
        '#default_value' => TRUE,
      ],
      'social_lets_connect_usage' => [
        '#title' => $this->t('Open Social usage data'),
        '#description' => $this->t('Shares usage data to the Open Social team. This helps us prioritise issues and new features making Open Social better.'),
        '#default_value' => TRUE,
      ],
      'social_group_flexible_group' => [
        '#title' => $this->t('Flexible groups'),
        '#description' => $this->t('Adds the flexible group type which allows users to create groups and choose the method of joining a group and its content visibility.'),
        '#default_value' => TRUE,
      ],
      'social_group_secret' => [
        '#title' => $this->t('Secret groups'),
        '#description' => $this->t("Adds the secret group type which allows users to create groups that are not shown on the platform unless you're a member."),
      ],
    ];
  }

}
