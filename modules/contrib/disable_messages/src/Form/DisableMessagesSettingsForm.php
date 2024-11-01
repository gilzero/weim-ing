<?php

namespace Drupal\disable_messages\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for administering disable messages.
 */
class DisableMessagesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'disable_messages_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Process and save the regular expressions in another variable.
    $patterns = explode("\n", $form_state->getValue('disable_messages_ignore_patterns'));
    $regexps = [];
    $disable_messages_ignore_case = $this->config('disable_messages.settings')->get('disable_messages_ignore_case');
    $ignore_case = ($disable_messages_ignore_case == '1') ? 'i' : '';
    foreach ($patterns as $pattern) {
      $pattern = preg_replace(['/^\s*/', '/\s*$/'], '', $pattern);
      $pattern = '/^' . $pattern . '$/' . $ignore_case;
      $regexps[] = $pattern;
    }

    $this->config('disable_messages.settings')
      ->set('disable_messages_enable', $form_state->getValue('disable_messages_enable'))
      ->set('disable_messages_exclude_users', $form_state->getValue('disable_messages_exclude_users'))
      ->set('disable_messages_filter_by_page', $form_state->getValue('disable_messages_filter_by_page'))
      ->set('disable_messages_page_filter_paths', $form_state->getValue('disable_messages_page_filter_paths'))
      ->set('disable_messages_ignore_patterns', $form_state->getValue('disable_messages_ignore_patterns'))
      ->set('disable_messages_ignore_regex', $regexps)
      ->set('disable_messages_enable_permissions', $form_state->getValue('disable_messages_enable_permissions'))
      ->set('disable_messages_enable_debug', $form_state->getValue('disable_messages_enable_debug'))
      ->set('disable_messages_strip_html_tags', $form_state->getValue('disable_messages_strip_html_tags'))
      ->set('disable_messages_ignore_case', $form_state->getValue('disable_messages_ignore_case'))
      ->set('disable_messages_debug_visible_div', $form_state->getValue('disable_messages_debug_visible_div'))
      ->set('disable_messages_ignore_exclude', $form_state->getValue('disable_messages_ignore_exclude'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['disable_messages_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable filtering'),
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_enable'),
      '#description' => $this->t('Uncheck this checkbox to disable all message filtering. If you uncheck this, all messages will be shown to all users and no custom filtering rules will be applied.'),
    ];
    $form['disable_messages_ignore_patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Messages to be disabled'),
      '#description' => $this->t('Enter messages that should not be shown to end users. Regular expressions are supported. You do not have to include the opening and closing forward slashes for the regular expression. The system will automatically add /^ and $/ at the beginning and end of the pattern to ensure that the match is always a full match instead of a partial match. This will help prevent unexpected filtering of messages. So if you want to filter out a specific message ensure that you add the full message including any punctuation and additional HTML if any. Add one per line. See <a href="@PCRE" target="_blank"> PCRE </a> documentation for details on regular expressions.', ['@PCRE' => 'https://us3.php.net/manual/en/book.pcre.php']),
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_ignore_patterns'),
    ];
    $form['disable_messages_strip_html_tags'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Strip HTMl tags'),
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_strip_html_tags'),
      '#description' => $this->t('Check this to strip the HTMl tags while matching the patterns.'),
    );
    $form['disable_messages_ignore_case'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore case'),
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_ignore_case'),
      '#description' => $this->t('Check this to ignore case while matching the patterns.'),
    ];
    $form['disable_messages_filter_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Page level filtering options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $options = [
      $this->t('Apply filters on all pages.'),
      $this->t('Apply filters on every page except the listed pages.'),
      $this->t('Apply filters only on the listed pages.'),
    ];
    $description = $this->t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
      [
        '%blog' => 'blog',
        '%blog-wildcard' => 'blog/*',
        '%front' => '<front>',
      ]
    );
    $form['disable_messages_filter_options']['disable_messages_filter_by_page'] = [
      '#type' => 'radios',
      '#title' => $this->t('Apply filters by page'),
      '#options' => $options,
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_filter_by_page'),
    ];
    $form['disable_messages_filter_options']['disable_messages_page_filter_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_page_filter_paths'),
      '#description' => $description,
    ];
    $form['disable_messages_filter_user_options'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('User level filtering options (Advanced)'),
    ];
    $form['disable_messages_filter_user_options']['role_information'] = [
      '#type' => 'item',
      '#title' => $this->t('Filtering by permissions'),
      '#markup' => $this->t(
        "By default, permission to view all message types are granted to all roles on install. If you enable this, you will have to explicitly configure the permissions for the roles which are expected to see the messages of the given type. Ideally, all roles should be given the permission to view status messages. You can set the permissions at <a href='@link' target='_blank'>administer permissions</a> to set the roles which can view a given message type. Also users with 'Exclude from filtering' permission will see all messages irrespective of the configuration. Admininstrator role will by default have this. You can use the debug option 'Ignore the exclude role permission' to test filtering even when logged in as an Administrator user.",
        [
          '@link' => Url::fromRoute(
            'user.admin_permissions', [], [
              'fragment' => 'module-disable_messages',
            ])->toString(),
        ]),
    ];
    $form['disable_messages_filter_user_options']['disable_messages_enable_permissions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable permission checking'),
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_enable_permissions'),
      '#description' => $this->t("Enable filtering messages by permissions assigned to roles. Read implication above before enabling this. The permission 'Exclude from filtering' is checked irrespective of this setting. The 'Exclude from filtering' is primarily given as an administratory capability to see all messages irrespective of filtering configuration. If you still want to test the filtering system as an administrator user, use the debug option 'Ignore the exclude role permission' below in the debug options."),
    ];
    $form['disable_messages_filter_user_options']['disable_messages_exclude_users'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Users excluded from filtering'),
      '#size' => 40,
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_exclude_users'),
      '#description' => $this->t("Comma separated list of user ids to be excluded from any filtering. All messages will be shown to all the listed users irrespective of their permissons to view the corresponding type of message."),
    ];
    $form['disable_messages_debug'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Debug options'),
    ];
    $form['disable_messages_debug']['disable_messages_enable_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_enable_debug'),
      '#description' => $this->t('Check this to enable debug information. The debug information will be shown in an explicitly hidden div sent to the page via $closure. You can use firebug or a similar tool like that to set the visibility of this div or just view source to see the debug information. Safe to use even on production sites.'),
    ];
    $form['disable_messages_debug']['disable_messages_debug_visible_div'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show debug div as visible'),
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_debug_visible_div'),
      '#description' => $this->t("Frustrated with having to view source everytime? Don't worry. Enable this to show the debug messages in a visible div. <strong>Remember to disable this on the production sites if you enable debug there :)</strong>."),
    ];
    $form['disable_messages_debug']['disable_messages_ignore_exclude'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore the exclude role permission'),
      '#default_value' => $this->config('disable_messages.settings')->get('disable_messages_ignore_exclude'),
      '#description' => $this->t(
        "Administrator role gets all the permissions. So they are excluded from
        filtering by default. Use this to ignore the exclude filtering permission
        to allow you to test the filtering without having to switch users."
      ),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    global $_disable_messages_error, $_disable_messages_error_no;
    $patterns = explode("\n", $form_state->getValues()['disable_messages_ignore_patterns']);
    // Override drupal error handler to handle the preg error here.
    set_error_handler('_disable_messages_error_handler');
    foreach ($patterns as $pattern) {
      $pattern = preg_replace(['/^\s*/', '/\s*$/'], '', $pattern);
      try {
        preg_match('/' . $pattern . '/', "This is a test string");
      }
      catch (\Exception $e) {
      }
      if ($_disable_messages_error) {
        $form_state->setErrorByName('disable_messages_ignore_patterns', $this->t('"@pattern" is not a valid regular expression. Preg error (@error_no) - @error',
          [
            '@pattern' => $pattern,
            '@error_no' => $_disable_messages_error_no,
            '@error' => $_disable_messages_error,
          ]
        ));
        restore_error_handler();
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'disable_messages.settings',
    ];
  }

}
