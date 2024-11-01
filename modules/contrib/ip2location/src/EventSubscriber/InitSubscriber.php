<?php
/**
 * @file
 * Contains \Drupal\ip2location\EventSubscriber\InitSubscriber.
 */

namespace Drupal\ip2location\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class InitSubscriber implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return [KernelEvents::REQUEST => ['onEvent']];
	}

	public function onEvent()
	{
		if (empty(\Drupal::service('session')->get('ip2location'))) {
			$config = \Drupal::config('ip2location.settings');

			$databasePath = $config->get('database_path');
			$cacheMode = $config->get('cache_mode');

			if (!file_exists($databasePath)) {
				return;
			}

			$ip = \Drupal::request()->getClientIp();

			if (isset($_SERVER['DEV_MODE'])) {
				$ip = '8.8.8.8';
			}

			switch ($cacheMode) {
				case 'memory_cache':
					$ip2location = new \IP2Location\Database($databasePath, \IP2Location\Database::MEMORY_CACHE);
					break;

				case 'shared_memory':
					$ip2location = new \IP2Location\Database($databasePath, \IP2Location\Database::SHARED_MEMORY);
					break;

				default:
					$ip2location = new \IP2Location\Database($databasePath, \IP2Location\Database::FILE_IO);
			}

			if (($records = $ip2location->lookup($ip, \IP2Location\Database::ALL)) !== false) {
				$raw = json_encode([
					'ip_address'           => $ip,
					'country_code'         => $records['countryCode'],
					'country_name'         => $records['countryName'],
					'region_name'          => $records['regionName'],
					'city_name'            => $records['cityName'],
					'latitude'             => $records['latitude'],
					'longitude'            => $records['longitude'],
					'isp'                  => $records['isp'],
					'domain_name'          => $records['domainName'],
					'zip_code'             => $records['zipCode'],
					'time_zone'            => $records['timeZone'],
					'net_speed'            => $records['netSpeed'],
					'idd_code'             => $records['iddCode'],
					'area_code'            => $records['areaCode'],
					'weather_station_code' => $records['weatherStationCode'],
					'weather_station_name' => $records['weatherStationName'],
					'mcc'                  => $records['mcc'],
					'mnc'                  => $records['mnc'],
					'mobile_carrier_name'  => $records['mobileCarrierName'],
					'elevation'            => $records['elevation'],
					'usage_type'           => $records['usageType'],
					'address_type'         => $records['addressType'],
					'category'             => $records['category'],
					'district'             => $records['district'],
					'as'                   => $records['as'],
					'asn'                  => $records['asn'],
				]);

				\Drupal::service('session')->set('ip2location', $raw);
			}
		}
	}
}
