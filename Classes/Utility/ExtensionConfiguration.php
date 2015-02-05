<?php
namespace Tx\FalDummy\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "fal_dummy".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Handles the extension configuration.
 */
class ExtensionConfiguration implements SingletonInterface {

	/**
	 * Returns the unserialized Extension Configuration.
	 *
	 * @return array
	 */
	public function getConfigurationArray() {

		if (empty($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fal_dummy'])) {
			return NULL;
		}

		$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fal_dummy']);

		if (!is_array($configuration)) {
			return NULL;
		}

		return $configuration;
	}
}
