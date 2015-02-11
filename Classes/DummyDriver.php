<?php
namespace Tx\FalDummy;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "fal_dummy".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This driver will use a placeholder service for displaying non existing images.
 */
class DummyDriver extends \TYPO3\CMS\Core\Resource\Driver\LocalDriver {

	/**
	 * Internal use to prevent recursion.
	 *
	 * @var bool
	 */
	protected $disableHasFileCheck = FALSE;

	/**
	 * @var int
	 */
	protected $imageMaxHeight = 1024;

	/**
	 * @var int
	 */
	protected $imageMaxWidth = 1024;

	/**
	 * @var string
	 */
	protected $placeholderServiceUrl = 'http://www.placecage.com/c/%d/%d';

	/**
	 * Initializes this object. This is called by the storage after the driver
	 * has been attached.
	 *
	 * @return void
	 */
	public function initialize() {

		parent::initialize();

		/** @var \Tx\FalDummy\Utility\ExtensionConfiguration $configuration */
		$configuration = GeneralUtility::makeInstance('Tx\\FalDummy\\Utility\\ExtensionConfiguration');
		$configuration = $configuration->getConfigurationArray();

		if (!empty($configuration['placeholderServiceUrl'])) {
			$this->placeholderServiceUrl = $configuration['placeholderServiceUrl'];
		}

		if (!empty($configuration['mageMaxWidth'] && $configuration['mageMaxWidth'] > 0)) {
			$this->imageMaxWidth = (int)$configuration['mageMaxWidth'];
		}

		if (!empty($configuration['imageMaxHeight'] && $configuration['imageMaxHeight'] > 0)) {
			$this->imageMaxHeight = (int)$configuration['imageMaxHeight'];
		}
	}

	/**
	 * Directly output the contents of the file to the output
	 * buffer. Should not take care of header files or flushing
	 * buffer before. Will be taken care of by the Storage.
	 *
	 * @param string $identifier
	 * @return void
	 */
	public function dumpFileContents($identifier) {

		if ($this->useParentDriver($identifier)) {
			parent::dumpFileContents($identifier);
		}

		echo $this->getFileContents($identifier);
	}

	/**
	 * Checks if a file exists.
	 *
	 * @param string $fileIdentifier
	 * @return boolean
	 */
	public function fileExists($fileIdentifier) {

		if ($this->disableHasFileCheck) {
			return TRUE;
		}

		if ($this->useParentDriver($fileIdentifier)) {
			return parent::fileExists($fileIdentifier);
		}

		return TRUE;
	}

	/**
	 * Returns the contents of a file. Beware that this requires to load the
	 * complete file into memory and also may require fetching the file from an
	 * external location. So this might be an expensive operation (both in terms
	 * of processing resources and money) for large files.
	 *
	 * @param string $fileIdentifier
	 * @return string The file contents
	 */
	public function getFileContents($fileIdentifier) {
		if ($this->useParentDriver($fileIdentifier)) {
			return parent::getFileContents($fileIdentifier);
		}

		$errorReport = array();
		$imageUrl = $this->getPublicUrl($fileIdentifier);
		$result = GeneralUtility::getUrl($imageUrl, 0, FALSE, $errorReport);

		if ($result === FALSE) {
			throw new \RuntimeException(sprintf('Error fetching placeholder image %s, occured error was: ', $imageUrl) . $errorReport['message']);
		}

		return $result;
	}

	/**
	 * Downloads the file from the placeholder service and stores it in the temporary file.
	 *
	 * @param string $fileIdentifier
	 * @param bool $writable
	 * @return string
	 */
	public function getFileForLocalProcessing($fileIdentifier, $writable = TRUE) {

		if ($this->useParentDriver($fileIdentifier)) {
			return parent::getFileForLocalProcessing($fileIdentifier, $writable);
		}

		$temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
		$content = $this->getFileContents($fileIdentifier);
		$result = file_put_contents($temporaryPath, $content);
		touch($temporaryPath, $this->getFileObjectByIdentifier($fileIdentifier)->getModificationTime());

		if ($result === FALSE) {
			throw new \RuntimeException('Copying file ' . $fileIdentifier . ' to temporary path failed.', 1320577649);
		}


		return $temporaryPath;
	}

	/**
	 * Returns the public URL to a file.
	 * Either fully qualified URL or relative to PATH_site (rawurlencoded).
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function getPublicUrl($identifier) {

		if ($this->useParentDriver($identifier)) {
			return parent::getPublicUrl($identifier);
		}

		$file = $this->getFileObjectByIdentifier($identifier);

		$width = $file->getProperty('width');
		$height = $file->getProperty('height');

		$this->calculateMaxWidthAndHeight($width, $height);

		$publicUrl = sprintf($this->placeholderServiceUrl, $width, $height);

		return $publicUrl;
	}

	/**
	 * Creates a (cryptographic) hash for a file.
	 *
	 * @param string $fileIdentifier
	 * @param string $hashAlgorithm The hash algorithm to use
	 * @return string
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function hash($fileIdentifier, $hashAlgorithm) {

		if ($this->useParentDriver($fileIdentifier)) {
			return parent::hash($fileIdentifier, $hashAlgorithm);
		}

		if (!in_array($hashAlgorithm, $this->supportedHashAlgorithms)) {
			throw new \InvalidArgumentException('Hash algorithm "' . $hashAlgorithm . '" is not supported.', 1304964032);
		}

		if ($hashAlgorithm === 'sha1') {
			return $this->getFileObjectByIdentifier($fileIdentifier)->getProperty('sha1');
		}

		$content = $this->getFileContents($fileIdentifier);
		return md5($content);

	}

	/**
	 * Makes sure that given with and height are within configured maximum values.
	 * Aspect ratio will be kept during downscaling.
	 *
	 * @param int $width
	 * @param int $height
	 */
	protected function calculateMaxWidthAndHeight(&$width, &$height) {

		$maxWidth = $this->imageMaxWidth;
		$maxHeight = $this->imageMaxHeight;

		$widthPercentage = 0;
		if ($width > $maxWidth) {
			$widthPercentage = $maxWidth / $width;
			$width = $maxWidth;
		}

		if ($widthPercentage !== 0) {
			$height = $height * $widthPercentage;
		}

		$heightPercentage = 0;
		if ($height > $maxHeight) {
			$heightPercentage = $maxHeight / $height;
			$height = $maxHeight;
		}

		if ($heightPercentage !== 0) {
			$width = $width * $heightPercentage;
		}

		$width = (int)$width;
		$height = (int)$height;
	}

	/**
	 *
	 * @param string $identifier
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	protected function getFileObjectByIdentifier($identifier) {
		return \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObjectFromCombinedIdentifier($this->storageUid . ':' . $identifier);
	}

	/**
	 * Checks if this driver should be used for the given file.
	 * We only use a dummy image if the real file does not exist and if the file is an image.
	 *
	 * @param string $fileIdentifier
	 * @return bool
	 */
	protected function useParentDriver($fileIdentifier) {

		// If $fileIdentifer points to a directory (last character is as slash)
		// we let the parent driver handle the request.
		if (substr($fileIdentifier, -1) === '/') {
			return TRUE;
		}

		if (parent::fileExists($fileIdentifier)) {
			return TRUE;
		}

		$this->disableHasFileCheck = TRUE;
		$file = $this->getFileObjectByIdentifier($fileIdentifier);
		$this->disableHasFileCheck = FALSE;

		if (isset($file)) {
			$fileProperties = $file->getProperties();

			if (isset($fileProperties['type']) && (int)$fileProperties['type'] === \TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_IMAGE) {
				return FALSE;
			}
		}

		return TRUE;
	}
}