<?php

namespace Drupal\simple_popup_blocks\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to add a database entry, with all the interesting fields.
 */
class SimplePopupBlocksEditForm extends SimplePopupBlocksAddForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_popup_blocks_edit_form';
  }

  protected function getEditableConfigNames() {
    return [
      'simple_popup_blocks_add_form.settings',
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL) {
    $configFactory = \Drupal::service('config.factory');
    $data = $configFactory->get('simple_popup_blocks.popup_'.$uid);

    $form = parent::buildForm($form, $form_state);
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable this as popup.'),
      '#default_value' => $data->get('status'),
      '#weight' => -99,
    ];

    $visit_counts = unserialize($data->get('visit_counts'));
    $identifier = $data->get('identifier');
    $form['uid']['#default_value'] = $data->get('uid');
    $form['uid']['#disabled'] = TRUE;
    $form['type']['#default_value'] = $data->get('type');
    $form['block_list']['#default_value'] = $identifier;
    $form['custom_css']['#default_value'] = $identifier;
    $form['css_selector']['#default_value'] = $data->get('css_selector');
    $form['layout']['#default_value'] = $data->get('layout');
    $form['minimize']['#default_value'] = $data->get('minimize');
    $form['close']['#default_value'] = $data->get('close');
    $form['enable_escape']['#default_value'] = $data->get('enable_escape');
    $form['overlay']['#default_value'] = $data->get('overlay');
    $form['trigger_method']['#default_value'] = $data->get('trigger_method');
    $form['trigger_selector']['#default_value'] = $data->get('trigger_selector');
    $form['delay']['#default_value'] = $data->get('delay');
	$form['trigger_width']['#default_value'] = $data->get('trigger_width');
    $form['width']['#default_value'] = $data->get('width');
    $form['cookie_expiry']['#default_value'] = $data->get('cookie_expiry');
    $form['button_configuration']['show_minimized_button']['#default_value'] = $data->get('show_minimized_button');

    $form['popup_frequency']['use_time_frequency']['#default_value'] = $data->get('use_time_frequency');
    $form['popup_frequency']['time_frequency']['#default_value'] = $data->get('time_frequency');
    $form['popup_frequency']['visit_counts']['#default_value'] = $visit_counts;	

    $block_id_append = '';
    if ($data->get('type') == 0) {
      $block_id_append = 'block-';
      $identifier = preg_replace('/[_]+/', '-', $identifier);
    }
    $identifier = $block_id_append . $identifier;

    $parent = "#spb-" . $identifier;
    $modal_class = "." . $identifier . "-modal";
    $modal_close_class = "." . $identifier . "-modal-close";
    $modal_minimize_class = "." . $identifier . "-modal-minimize";
    $modal_minimized_class = "." . $identifier . "-modal-minimized";

    $positions = [
      0 => $this
        ->t('spb_top_left'),
      1 => $this
        ->t('spb_top_right'),
      2 => $this
        ->t('spb_bottom_left'),
      3 => $this
        ->t('spb_bottom_right'),
      4 => $this
        ->t('spb_center'),
      5 => $this
        ->t('spb_top_center'),
      6 => $this
        ->t('spb_top_bar'),
      7 => $this
        ->t('spb_bottom_bar'),
      8 => $this
        ->t('spb_left_bar'),
      9 => $this
        ->t('spb_right_bar'),
    ];
    $override_positions = $modal_class . ' .' . $positions[$data->get('layout')];
    $css_selector = '#';
    if ($data->get('type') && $data->get('css_selector') == 0) {
      $css_selector = '.';
    }
    $form['adjustments']['#description'] = $this->t('Use the following css selectors to customize the popup designs.');

    $rows = [
      ['Parent', $parent],
      ['Identifier', $css_selector . $identifier],
      ['Modal class', $modal_class],
      ['Modal close class', $modal_close_class],
      ['Modal minimize class', $modal_minimize_class],
      ['Modal minimized class', $modal_minimized_class],
      ['Override positions', $override_positions],
    ];

    $form['adjustments']['table'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#empty' => t('No results found'),
    ];
    $documentation = Url::fromUri('https://www.drupal.org/docs/8/modules/simple-popup-blocks');
    $documentation = Link::fromTextAndUrl($this->t('here'), $documentation);
    $documentation = $documentation->toRenderable();
    $documentation = \Drupal::service('renderer')->render($documentation);
    $display_none = "<p><h3>Add this css code in your css file (Recommended)</h3><p>";
    $display_none .= "<code>
      " . $css_selector . $identifier . " {<br>
      &nbsp;&nbsp;display: none;<br>
      }</code><br>";
    $display_none .= "<p>While page loading with slow internet connection, content of popup will be visible to users. So you should add this css code in your theme or any css file.</p>";

    $display_none .= "<p>See the documentation " . $documentation . "</p>";

    $form['adjustments']['display_none'] = [
      '#markup' => $display_none,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get a value by key.
    $first = $form_state->get('simple_popup_blocks_id');
    if ($form_state->getValue('type') == 0) {
      $selector = 'block_list';
      $identifier = $form_state->getValue($selector);
    }
    else {
      $selector = 'custom_css';
      $identifier = $form_state->getValue($selector);
    }
    if (!isset($identifier) || empty($identifier)) {
      $form_state->setError($form[$selector], $this->t('You should provide some css selector.'));
    }
    if ($form_state->getValue('type') == 1) {
      // Get the first character using substr.
      $firstCharacter = substr($identifier, 0, 1);
      if (in_array($firstCharacter, ['.', '#'])) {
        $form_state->setError($form[$selector], $this->t('Selector should not start with . or # in %field.', ['%field' => $identifier]));
      }
    }
    $trigger_method = $form_state->getValue('trigger_method');
    if ($trigger_method == 1) {
      $trigger_selector = $form_state->getValue('trigger_selector');
      // Get the first character using substr.
      $firstCharacter = substr($trigger_selector, 0, 1);
      if (!in_array($firstCharacter, ['.', '#'])) {
        $form_state->setError($form['trigger_selector'], $this->t('Selector should start with . or # only in %field.', ['%field' => $trigger_selector]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('type') == 0) {
      $identifier = $form_state->getValue('block_list');
    }
    else {
      $identifier = $form_state->getValue('custom_css');
    }

    $delay = $form_state->getValue('delay');
    if (empty($delay) || $delay < 0) {
      $delay = 0;
    }
    $trigger_width = $form_state->getValue('trigger_width');
    if (empty($trigger_width) || $trigger_width < 0) {
      $trigger_width = NULL;
    }
    $width = $form_state->getValue('width');
    if (empty($width) || $width < 0) {
      $width = 400;
    }
    $cookie_expiry = $form_state->getValue('cookie_expiry');
    if (strlen($cookie_expiry) == 0 || $cookie_expiry < 0) {
      $cookie_expiry = 100;
    }
    if ($form_state->getValue('use_time_frequency')) {
      $visit_counts = serialize([0 => '0']);
    }
    else {
      $visit_counts = serialize($form_state->getValue('visit_counts'));
    }

    $config = \Drupal::service('config.factory')->getEditable('simple_popup_blocks.popup_'.$form_state->getValue('uid'));
    $config->set('identifier', $identifier)
      ->set('type', $form_state->getValue('type'))
      ->set('css_selector', $form_state->getValue('css_selector'))
      ->set('layout', $form_state->getValue('layout'))
      ->set('visit_counts', $visit_counts)
      ->set('overlay', $form_state->getValue('overlay'))
      ->set('trigger_method', $form_state->getValue('trigger_method'))
      ->set('trigger_selector', $form_state->getValue('trigger_selector'))
      ->set('enable_escape', $form_state->getValue('enable_escape'))
      ->set('delay', $delay)
	  ->set('trigger_width', $trigger_width)
      ->set('minimize', $form_state->getValue('minimize'))
      ->set('close', $form_state->getValue('close'))
      ->set('width', $width)
      ->set('cookie_expiry', $cookie_expiry)
      ->set('status', $form_state->getValue('status'))
      ->set('use_time_frequency', $form_state->getValue('use_time_frequency'))
      ->set('time_frequency', $form_state->getValue('time_frequency'))
      ->set('show_minimized_button', $form_state->getValue('show_minimized_button'))		  
      ->save();
    parent::submitForm($form, $form_state);

  }
}
