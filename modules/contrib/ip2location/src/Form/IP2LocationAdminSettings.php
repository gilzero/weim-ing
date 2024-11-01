<?php

/**
 * @file
 * Contains \Drupal\ip2location\Form\IP2LocationAdminSettings.
 */

namespace Drupal\ip2location\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IP2LocationAdminSettings extends ConfigFormBase
{
	public function getFormId()
	{
		return 'ip2location_admin_settings';
	}

	public function getEditableConfigNames()
	{
		return [
			'ip2location.settings',
		];
	}

	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		$config = $this->config('ip2location.settings');

		foreach (Element::children($form) as $variable) {
			$config->set($variable, $form_state->getValue($form[$variable]['#parents']));
		}
		$config->save();

		if (method_exists($this, '_submitForm')) {
			$this->_submitForm($form, $form_state);
		}

		parent::submitForm($form, $form_state);
	}

	public function buildForm(array $form, FormStateInterface $form_state)
	{
		$config = $this->config('ip2location.settings');

		$database_path = $config->get('database_path');
		$cache_mode = $config->get('cache_mode');

		if (!in_array($cache_mode, ['no_cache', 'memory_cache', 'shared_memory'])) {
			$cache_mode = 'no_cache';
		}

		$form['database_path'] = [
			'#type'          => 'textfield',
			'#title'         => t('IP2Location BIN database path'),
			'#description'   => t('Relative path to your Drupal installation of to where the IP2Location BIN database was uploaded. For example: sites/default/files/IP2Location-LITE-DB11.BIN. Note: You can get the latest BIN data at <a href="http://lite.ip2location.com/?r=drupal" target="_blank">http://lite.ip2location.com</a> (free LITE edition) or <a href="http://www.ip2location.com/?r=drupal" target="_blank">http://www.ip2location.com</a> (commercial edition).'),
			'#default_value' => $database_path,
			'#states'        => [
				'visible' => [
					':input[name="ip2location_source"]' => [
						'value' => 'ip2location_bin',
					],
				],
			],
		];

		$form['cache_mode'] = [
			'#type'        => 'select',
			'#title'       => t('Cache Mode'),
			'#description' => t('"No cache" - standard lookup with no cache. "Memory cache" - cache the database into memory to accelerate lookup speed. "Shared memory" - cache whole database into system memory and share among other scripts and websites. Please make sure your system have sufficient RAM if enabling "Memory cache" or "Shared memory".'),
			'#options'     => [
				'no_cache'      => t('No cache'),
				'memory_cache'  => t('Memory cache'),
				'shared_memory' => t('Shared memory'),
			],
			'#default_value' => $cache_mode,
		];

		return parent::buildForm($form, $form_state);
	}

	public function validateForm(array &$form, FormStateInterface $form_state)
	{
		if (!is_file($form_state->getValue('database_path'))) {
			$form_state->setErrorByName('database_path', $this->t('The IP2Location binary database path is not valid.'));
		} else {
			try {
				$ip2location = new \IP2Location\Database($form_state->getValue('database_path'), \IP2Location\Database::FILE_IO);
				$records = $ip2location->lookup('8.8.8.8', \IP2Location\Database::ALL);

				if (empty($records['ipNumber'])) {
					$form_state->setErrorByName('database_path', $this->t('The IP2Location binary database is not valid or corrupted.'));
				}
			} catch (Exception $error) {
				$form_state->setErrorByName('database_path', $this->t('The IP2Location binary database is not valid or corrupted.'));
			}
		}
	}
}
