<?php
namespace LAM\PERSISTENCE;
use LAMCfgMain;
use LAMConfig;
use LAMException;
use function LAM\PROFILES\getAccountProfiles;
use function LAM\PROFILES\loadAccountProfile;
use function LAM\PROFILES\saveAccountProfile;

/*

  This code is part of LDAP Account Manager (http://www.ldap-account-manager.org/)
  Copyright (C) 2020  Roland Gruber

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
 * This file includes functions to manage the persistence of LAM's configuration files.
 *
 * @package configuration
 * @author Roland Gruber
 */

include_once __DIR__ . '/config.inc';
include_once __DIR__ . '/profiles.inc';

/**
 * Exporter for LAM's configuration data.
 */
class ConfigDataExporter {

	/**
	 * Exports LAM's configuration data in JSON format.
	 */
	public function exportAsJson() {
		$mainCfg = $this->_getMainConfiguration();
		$jsonData = array();
		$jsonData['mainConfig'] = $this->_getMainConfigData($mainCfg);
		$jsonData['certificates'] = $this->_getCertificates($mainCfg);
		$serverProfileNames = getConfigProfiles();
		$serverProfiles = array();
		foreach ($serverProfileNames as $serverProfileName) {
			$serverProfiles[$serverProfileName] = new \LAMConfig($serverProfileName);
		}
		$jsonData['serverProfiles'] = $this->_getServerProfiles($serverProfiles);
		$jsonData['accountProfiles'] = $this->_getAccountProfiles($serverProfiles);
		/**
		 * TODO
		 *
		 * account profiles
		 * PDF profiles
		 * self service profiles
		 * webauthn
		 */
		return json_encode($jsonData);
	}

	/**
	 * Returns the main configuration.
	 *
	 * @return LAMCfgMain main config
	 */
	public function _getMainConfiguration() {
		return new LAMCfgMain();
	}

	/**
	 * Internal function to read master configuration.
	 *
	 * @param LAMCfgMain $mainCfg main config
	 * @return array data
	 */
	public function _getMainConfigData($mainCfg) {
		return $mainCfg->exportData();
	}

	/**
	 * Returns the certificate file content.
	 *
	 * @param LAMCfgMain $mainCfg main config
	 * @return array data
	 */
	public function _getCertificates($mainCfg) {
		return $mainCfg->exportCertificates();
	}

	/**
	 * Returns the content of the server profiles.
	 *
	 * @param array $serverProfiles list of server profiles (name => object)
	 * @return array $data
	 */
	public function _getServerProfiles($serverProfiles) {
		$data = array();
		foreach ($serverProfiles as $profileName => $serverProfile) {
			$data[$profileName] = $serverProfile->exportData();
		}
		return $data;
	}

	/**
	 * Returns the content of the account profiles.
	 *
	 * @param array $serverProfiles list of server profiles (name => object)
	 * @return array $data
	 */
	public function _getAccountProfiles($serverProfiles) {
		$data = array();
		foreach ($serverProfiles as $profileName => $serverProfile) {
			foreach ($serverProfile->get_ActiveTypes() as $typeId) {
				$accountProfileNames = getAccountProfiles($typeId, $profileName);
				foreach ($accountProfileNames as $accountProfileName) {
					$accountProfile = loadAccountProfile($accountProfileName, $typeId, $profileName);
					$data[$profileName][$typeId][$accountProfileName] = $accountProfile;
				}
			}
		}
		return $data;
	}

}

/**
 * Importer for LAM's configuration data.
 */
class ConfigDataImporter {

	/**
	 * Returns a list of possible import objects.
	 *
	 * @param string $json JSON data
	 * @return ImporterStep[] steps
	 * @throws LAMException if invalid format
	 */
	public function getPossibleImportSteps($json) {
		$data = json_decode($json, true);
		if ($data === null) {
			throw new LAMException(_('Unable to read import file.'));
		}
		$steps = array();
		foreach ($data as $key => $value) {
			switch ($key) {
				case 'mainConfig':
					$steps[] = new ImporterStep(_('General settings'), 'mainConfig', $value);
					break;
				case 'certificates':
					$steps[] = new ImporterStep(_('SSL certificates'), 'certificates', $value);
					break;
				case 'serverProfiles':
					$mainStep = new ImporterStep(_('Server profiles'), 'serverProfiles', $value);
					foreach ($value as $profileName => $profileData) {
						$mainStep->addSubStep(new ImporterStep($profileName, 'serverProfile_' . $profileName, $profileData));
					}
					$steps[] = $mainStep;
					break;
				case 'accountProfiles':
					$mainStep = new ImporterStep(_('Account profiles'), 'accountProfiles', $value);
					foreach ($value as $profileName => $profileData) {
						$mainStep->addSubStep(new ImporterStep($profileName, 'accountProfile_' . $profileName, $profileData));
					}
					$steps[] = $mainStep;
					break;
				default:
					logNewMessage(LOG_WARNING, 'Unknown import type: ' . $key);
			}
		}
		if (empty($steps)) {
			throw new LAMException(_('Unable to read import file.'));
		}
		return $steps;
	}

	/**
	 * Runs the actual import.
	 *
	 * @param ImporterStep[] $steps import steps
	 * @throws LAMException if error occurred
	 */
	public function runImport($steps) {
		foreach ($steps as $step) {
			if (!$step->isActive()) {
				continue;
			}
			$key = $step->getKey();
			switch ($key) {
				case 'mainConfig':
					$this->importMainConfig($step->getValue());
					break;
				case 'certificates':
					$this->importCertificates($step->getValue());
					break;
				case 'serverProfiles':
					$this->importServerProfiles($step);
					break;
				case 'accountProfiles':
					$this->importAccountProfiles($step);
					break;
				default:
					logNewMessage(LOG_WARNING, 'Unknown import type: ' . $key);
			}
		}
	}

	/**
	 * Imports the main configuration.
	 *
	 * @param array $data main config data
	 * @throws LAMException error during import
	 */
	private function importMainConfig($data) {
		$cfgMain = new LAMCfgMain();
		$cfgMain->importData($data);
		$cfgMain->save();
	}

	/**
	 * Imports the SSL certificates.
	 *
	 * @param null|string $data file content
	 * @throws LAMException error during import
	 */
	private function importCertificates($data) {
		$cfgMain = new LAMCfgMain();
		$cfgMain->importCertificates($data);
	}

	/**
	 * Imports the server profiles.
	 *
	 * @param ImporterStep $step step
	 * @throws LAMException error during import
	 */
	private function importServerProfiles($step) {
		$failedProfiles = array();
		foreach ($step->getSubSteps() as $profileStep) {
			if (!$profileStep->isActive()) {
				continue;
			}
			$data = $profileStep->getValue();
			$profileName = str_replace('serverProfile_', '', $profileStep->getKey());
			$serverProfile = new LAMConfig($profileName);
			$serverProfile->importData($data);
			$result = $serverProfile->save();
			if ($result === LAMConfig::SAVE_FAIL) {
				$failedProfiles[] = $profileName;
			}
		}
		if (!empty($failedProfiles)) {
			throw new LAMException(_('Unable to save server profile.'), implode(', ', $failedProfiles));
		}
	}

	/**
	 * Imports the account profiles.
	 *
	 * @param ImporterStep $step step
	 * @throws LAMException error during import
	 */
	private function importAccountProfiles($step) {
		$failedProfiles = array();
		foreach ($step->getSubSteps() as $profileStep) {
			if (!$profileStep->isActive()) {
				continue;
			}
			$data = $profileStep->getValue();
			$serverProfileName = str_replace('accountProfile_', '', $profileStep->getKey());
			$serverProfile = new LAMConfig($serverProfileName);
			foreach ($data as $typeId => $accountProfiles) {
				foreach ($accountProfiles as $accountProfileName => $accountProfileData) {
					$result = saveAccountProfile($accountProfileData, $accountProfileName, $typeId, $serverProfile);
					if (!$result) {
						$failedProfiles[] = $serverProfileName . ':' . $typeId . ':' . $accountProfileName;
					}
				}
			}
		}
		if (!empty($failedProfiles)) {
			throw new LAMException(_('Unable to save account profile.'), implode(', ', $failedProfiles));
		}
	}

}

/**
 * Step of the import process.
 */
class ImporterStep {

	private $label;
	private $key;
	private $value;
	private $active = false;
	private $subSteps = array();

	/**
	 * Constructor.
	 *
	 * @param string $label label
	 * @param string $key key
	 * @param array $value value
	 */
	public function __construct($label, $key, $value) {
		$this->label = $label;
		$this->key = $key;
		$this->value = $value;
	}

	/**
	 * Returns the label.
	 *
	 * @return string label
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * Returns the key.
	 *
	 * @return string key
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * Returns if this step should be executed.
	 *
	 * @return bool active
	 */
	public function isActive(): bool {
		return $this->active;
	}

	/**
	 * Sets if this step should be executed.
	 *
	 * @param bool $active active
	 */
	public function setActive(bool $active) {
		$this->active = $active;
	}

	/**
	 * Returns the value.
	 *
	 * @return string value
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Adds a sub-step.
	 *
	 * @param ImporterStep $subStep sub-step
	 */
	public function addSubStep($subStep) {
		$this->subSteps[] = $subStep;
	}

	/**
	 * Returns the sub-steps.
	 *
	 * @return ImporterStep[] sub-steps
	 */
	public function getSubSteps() {
		return $this->subSteps;
	}

}