<?php

namespace Drupal\matomo_reports\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\matomo_reports\MatomoData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The controller for the reports display.
 */
class MatomoReportsController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('messenger')
    );
  }

  /**
   * Constructs a MatomoReportsController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(FormBuilderInterface $form_builder, MessengerInterface $messenger) {
    $this->formBuilder = $form_builder;
    $this->messenger = $messenger;
  }

  /**
   * Reports.
   *
   * @return array
   *   Return Reports render array.
   */
  public function reports(Request $request, $report) {
    $token_auth = MatomoData::getToken();
    $session = $request->getSession();
    if (!$token_auth) {
      $session->set('matomo_reports_site', '');
      $this->messenger->addWarning($this->t('A valid token authentication string has not been entered. Please check authentication string and permissions with your Matomo server administrator.'));
      return [];
    }
    else {
      $sites = MatomoData::getSites($token_auth);
      if (!$sites) {
        $this->messenger->addWarning($this->t('You cannot access any data on the selected Matomo server. Please check with your Matomo administrator for allowed sites.'));
        return [];
      }
    }

    $build['reports_form'] = $this->formBuilder->getForm('Drupal\matomo_reports\Form\ReportsForm', $sites = $sites);
    $matomo_site_id = $session->get('matomo_reports_site');
    $date = $session->get('matomo_reports_period') ?? 'today';
    $date_from = $session->get('matomo_reports_date_from') ?? NULL;
    $date_to = $session->get('matomo_reports_date_to') ?? NULL;

    if (!empty($date_from) && !empty($date_to)) {
      $period_name = 'range';

      // &period=range&date=2011-02-15,2011-02-25
      $date = Xss::filter($date_from) . ',' . Xss::filter($date_to);
    }
    else {
      $period_name = $this->getPeriodName($date);
    }

    // Create an array of URL parameters for easier maintenance.
    $title = [];
    $data_params[0] = [];
    $data_params[0]['idSite'] = $matomo_site_id;
    $data_params[0]['date'] = $date;
    $data_params[0]['period'] = $period_name;
    $data_params[0]['disableLink'] = 1;
    $data_params[0]['module'] = 'Widgetize';
    $data_params[0]['action'] = 'iframe';
    $data_params[0]['widget'] = 1;
    $data_params[0]['force_api_session'] = 1;
    $data_params[0]['token_auth'] = $token_auth;

    switch ($report) {
      case 'visitors_overview':
        $iframe_height[0] = 950;
        $title[0] = '';
        $data_params[0]['moduleToWidgetize'] = 'VisitsSummary';
        $data_params[0]['actionToWidgetize'] = 'index';

        break;

      case 'visitors_times':
        $title[0] = $this->t('Visits by Local Time');
        $data_params[0]['moduleToWidgetize'] = 'VisitTime';
        $data_params[0]['actionToWidgetize'] = 'getVisitInformationPerLocalTime';
        break;

      case 'visitors_settings':
        $data_params[0]['filter_limit'] = 6;

        $data_params[1] = $data_params[0];
        $data_params[2] = $data_params[0];
        $data_params[3] = $data_params[0];
        // Browser families.
        $title[0] = $this->t('Browser families');
        $data_params[0]['moduleToWidgetize'] = 'DevicesDetection';
        $data_params[0]['actionToWidgetize'] = 'getBrowserEngines';
        // Screen resolutions.
        $title[1] = $this->t('Screen resolution');
        $data_params[1]['moduleToWidgetize'] = 'Resolution';
        $data_params[1]['actionToWidgetize'] = 'getConfiguration';
        // Operating systems.
        $title[2] = $this->t('Operating system');
        $data_params[2]['moduleToWidgetize'] = 'DevicesDetection';
        $data_params[2]['actionToWidgetize'] = 'getOsVersions';
        // Client configurations.
        $title[3] = $this->t('Client configuration');
        $data_params[3]['moduleToWidgetize'] = 'Resolution';
        $data_params[3]['actionToWidgetize'] = 'getResolution';
        break;

      case 'visitors_locations':
        $title[0] = $this->t('Visitors Countries');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'UserCountry';
        $data_params[0]['actionToWidgetize'] = 'getCountry';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'visitors_variables':
        $title[0] = $this->t('Custom Variables');
        $iframe_height[0] = 1000;
        $data_params[0]['moduleToWidgetize'] = 'CustomVariables';
        $data_params[0]['actionToWidgetize'] = 'getCustomVariables';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'actions_pages':
        $title[0] = $this->t('Page Visits');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getPageUrls';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'actions_entrypages':
        $title[0] = $this->t('Entry Pages');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getEntryPageUrls';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'actions_exitpages':
        $title[0] = $this->t('Exit Pages');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getExitPageUrls';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'actions_sitesearch':
        $data_params[1] = $data_params[0];
        $data_params[2] = $data_params[0];
        $data_params[3] = $data_params[0];

        $title[0] = $this->t('Site Search Keywords');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getSiteSearchKeywords';
        $data_params[0]['filter_limit'] = 15;
        // Pages following search.
        $title[1] = $this->t('Pages Following a Site Search');
        $data_params[1]['moduleToWidgetize'] = 'Actions';
        $data_params[1]['actionToWidgetize'] = 'getPageUrlsFollowingSiteSearch';
        // No results.
        $title[2] = $this->t('Site Search No Result Keyword');
        $data_params[2]['moduleToWidgetize'] = 'Actions';
        $data_params[2]['actionToWidgetize'] = 'getSiteSearchNoResultKeywords';
        // Categories.
        $title[3] = $this->t('Site Search Categories');
        $data_params[3]['moduleToWidgetize'] = 'Actions';
        $data_params[3]['actionToWidgetize'] = 'getSiteSearchCategories';
        break;

      case 'actions_outlinks':
        $title[0] = $this->t('Outlinks');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getOutlinks';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'actions_downloads':
        $title[0] = $this->t('Downloads');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getDownloads';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'events':
        $title[0] = $this->t('Events');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Events';
        $data_params[0]['actionToWidgetize'] = 'getCategory';
        $data_params[0]['secondaryDimension'] = 'eventAction';
        break;

      case 'referrers_allreferrers':
        $data_params[1] = $data_params[0];
        // Types.
        $title[0] = $this->t('Referrer Types');
        $iframe_height[0] = 250;
        $data_params[0]['moduleToWidgetize'] = 'Referrers';
        $data_params[0]['actionToWidgetize'] = 'getReferrerType';
        // Referrers.
        $title[1] = $this->t('Referrers');
        $data_params[1]['moduleToWidgetize'] = 'Referrers';
        $data_params[1]['actionToWidgetize'] = 'getAll';
        break;

      case 'referrers_search':
        $data_params[1] = $data_params[0];

        $title[0] = $this->t('Search Engines');
        $data_params[0]['moduleToWidgetize'] = 'Referrers';
        $data_params[0]['actionToWidgetize'] = 'getSearchEngines';

        $title[1] = $this->t('Keywords');
        $data_params[1]['moduleToWidgetize'] = 'Referrers';
        $data_params[1]['actionToWidgetize'] = 'getKeywords';
        break;

      case 'referrers_websites':
        $data_params[1] = $data_params[0];
        $title[0] = $this->t('Websites');
        $iframe_height[0] = 1020;
        $data_params[0]['moduleToWidgetize'] = 'Referrers';
        $data_params[0]['actionToWidgetize'] = 'getWebsites';

        $title[1] = $this->t('Social Networks');
        $data_params[1]['moduleToWidgetize'] = 'Referrers';
        $data_params[1]['actionToWidgetize'] = 'getSocials';
        break;

      case 'referrers_campaigns':
        $title[0] = $this->t('Campaigns');
        $data_params[0]['moduleToWidgetize'] = 'Referrers';
        $data_params[0]['actionToWidgetize'] = 'getCampaigns';
        break;

      case 'goals':
        $goals = $this->getGoals($token_auth, $session->get('matomo_reports_site'));
        if (count($goals) == 0) {
          $empty_text = $this->t('No goals have been set. Check with your Matomo server administrator if you desire some.');
          $title[0] = NULL;
          break;
        }
        $common_data_params = $data_params[0];
        $i = 0;
        foreach ($goals as $goal) {
          $title[$i] = $goal['name'];
          $data_params[$i] = $common_data_params;
          $data_params[$i]['moduleToWidgetize'] = 'Goals';
          $data_params[$i]['actionToWidgetize'] = 'widgetGoalReport';
          $data_params[$i]['idGoal'] = $goal['idgoal'];
          $i++;
        }
        break;

      case 'transitions':
        $title[0] = $this->t('Transitions');
        $data_params[0]['moduleToWidgetize'] = 'Transitions';
        $data_params[0]['actionToWidgetize'] = 'getTransitions';
        $iframe_height[0] = 1000;

        break;
    }
    $request->setSession($session);
    // Build the data URL with all params and urlencode it.
    foreach ($data_params as $key => $data) {
      $theme_args[] = [
        'url' => MatomoData::getUrl() . 'index.php?' . http_build_query($data),
        'title' => $title[$key],
        'iframe_height' => (isset($iframe_height[$key]) && $iframe_height[$key] > 0 ? $iframe_height[$key] : 400),
        'empty_text' => ($empty_text ?? NULL),
      ];
    }
    $build['content'] = [
      '#theme' => 'matomo_reports',
      '#data_url' => $theme_args,
    ];

    return $build;
  }

  /**
   * Return a list of goals active on selected site.
   *
   * @param string $token_auth
   *   Matomo server token auth.
   * @param string $site
   *   Selected site id.
   *
   * @return array|string|bool
   *   Goals returned from Matomo reports API.
   */
  private function getGoals($token_auth, $site) {
    $matomo_url = MatomoData::getUrl();
    if ($matomo_url) {
      $options = [
        'form_params' => [
          'module' => 'API',
          'method' => 'Goals.getGoals',
          'idSite' => (int) $site,
          'format' => 'JSON',
          'token_auth' => $token_auth,
          'force_api_session' => 1,
        ],
      ];
      return MatomoData::getResponse($matomo_url . 'index.php', $options, 'POST');
    }
    else {
      return FALSE;
    }
  }

  /**
   * Helper function to return the name of the selected period.
   *
   * @param int $period
   *   Selected period.
   *
   * @return string
   *   Name of period.
   */
  private function getPeriodName($period) {
    return match ($period) {
      'last week' => 'week',
      'last month' => 'month',
      'last year' => 'year',
      default => 'day',
    };
  }

}
