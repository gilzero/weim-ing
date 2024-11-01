<?php

namespace Drupal\xray_audit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\xray_audit\Services\NavigationArchitectureInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form to select the menu to show.
 */
final class MenuSelectorForm extends FormBase {

  /**
   * The navigation architecture service.
   *
   * @var \Drupal\xray_audit\Services\NavigationArchitectureInterface
   */
  protected $navigationArchitecture;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor for MenuSelectorForm.
   *
   * @param \Drupal\xray_audit\Services\NavigationArchitectureInterface $navigation_architecture
   *   The navigation architecture service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(NavigationArchitectureInterface $navigation_architecture, RequestStack $request_stack) {
    $this->navigationArchitecture = $navigation_architecture;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('xray_audit.navigation_architecture'),
      $container->get('request_stack'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "xray_audit_menu_selector_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @phpstan-ignore-next-line
    $menu_options = $this->navigationArchitecture->getMenuList();
    // @phpstan-ignore-next-line
    $request = $this->requestStack->getCurrentRequest();

    $default_value = (isset($menu_options['main'])) ? 'main' : '';

    $form['menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Menu'),
      '#options' => $menu_options,
      '#default_value' => ($request !== NULL) ? $request->query->get('menu', $default_value) : $default_value,
    ];

    // Add checkbox to show only the menu items with a link.
    $form['show_parent_reference'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show parent references in level columns'),
      '#default_value' => ($request !== NULL) ? $request->query->get('show_parent_reference', FALSE) : FALSE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('View Menu'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query_parameters = [
      'query' => [
        'menu' => $form_state->getValue('menu'),
        'show_parent_reference' => $form_state->getValue('show_parent_reference'),
      ],
    ];

    $url = Url::fromRoute('xray_audit.task_page.menu', [], $query_parameters);
    $form_state->setRedirectUrl($url);
  }

}
