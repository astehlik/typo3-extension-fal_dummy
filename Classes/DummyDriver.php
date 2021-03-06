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
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This driver will use a placeholder service for displaying non existing images.
 */
class DummyDriver extends \TYPO3\CMS\Core\Resource\Driver\LocalDriver
{
    /**
     * Internal use to prevent recursion.
     *
     * @var bool
     */
    protected $disableHasFileCheck = false;

    /**
     * @var int
     */
    protected $imageMaxHeight = 1024;

    /**
     * @var int
     */
    protected $imageMaxWidth = 1024;

    /**
     * The path to the local dummy resources.
     *
     * @var string
     */
    protected $localDummyResourcePath;

    /**
     * @var string
     */
    protected $placeholderServiceUrl = 'http://www.placecage.com/c/%d/%d';

    /**
     * If this is TRUE a the local resources will be used instead of the placeholder service.
     *
     * @var bool
     */
    protected $useLocalFilesIfAvailable = false;

    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->localDummyResourcePath = GeneralUtility::getFileAbsFileName(
            'EXT:fal_dummy/Resources/Public/DummyFiles/'
        );

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

        if (isset($configuration['useLocalFilesIfAvailable'])) {
            $this->useLocalFilesIfAvailable = (bool)$configuration['useLocalFilesIfAvailable'];
        } else {
            $this->useLocalFilesIfAvailable = true;
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
    public function dumpFileContents($identifier)
    {
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
    public function fileExists($fileIdentifier)
    {
        if ($this->disableHasFileCheck) {
            return true;
        }

        if ($this->useParentDriver($fileIdentifier)) {
            return parent::fileExists($fileIdentifier);
        }

        return true;
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
    public function getFileContents($fileIdentifier)
    {
        if ($this->useParentDriver($fileIdentifier)) {
            return parent::getFileContents($fileIdentifier);
        }

        $file = $this->getFileObjectByIdentifier($fileIdentifier);
        $dummyFile = $this->getDummyFileObject($file);
        if ($dummyFile) {
            return $dummyFile->getContents();
        }

        $errorReport = [];
        $imageUrl = $this->getPublicUrl($fileIdentifier);
        $result = GeneralUtility::getUrl($imageUrl, 0, false, $errorReport);

        if ($result === false) {
            throw new \RuntimeException(
                sprintf('Error fetching placeholder image %s, occured error was: ', $imageUrl) . $errorReport['message']
            );
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
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        if ($this->useParentDriver($fileIdentifier)) {
            return parent::getFileForLocalProcessing($fileIdentifier, $writable);
        }

        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
        $content = $this->getFileContents($fileIdentifier);
        $result = file_put_contents($temporaryPath, $content);
        touch($temporaryPath, $this->getFileObjectByIdentifier($fileIdentifier)->getModificationTime());

        if ($result === false) {
            throw new \RuntimeException('Copying file ' . $fileIdentifier . ' to temporary path failed.', 1320577649);
        }

        return $temporaryPath;
    }

    /**
     * Returns information about a folder, no matter if it exists.
     *
     * @param string $folderIdentifier In the case of the LocalDriver, this is the (relative) path to the file.
     * @return array
     * @throws \TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException
     */
    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        if (is_dir($this->getAbsolutePath($folderIdentifier))) {
            return parent::getFolderInfoByIdentifier($folderIdentifier);
        }

        return [
            'identifier' => $folderIdentifier,
            'name' => PathUtility::basename($folderIdentifier),
            'storage' => $this->storageUid,
        ];
    }

    /**
     * We always return read and write permissions if the file does not exist.
     *
     * @param string $identifier
     * @return array
     * @throws \RuntimeException
     */
    public function getPermissions($identifier)
    {
        if (file_exists($this->getAbsolutePath($identifier))) {
            return parent::getPermissions($identifier);
        }

        return [
            'r' => true,
            'w' => true,
        ];
    }

    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to PATH_site (rawurlencoded).
     *
     * @param string $identifier
     * @return string
     */
    public function getPublicUrl($identifier)
    {
        if ($this->useParentDriver($identifier)) {
            return parent::getPublicUrl($identifier);
        }

        $file = $this->getFileObjectByIdentifier($identifier);

        $width = $file->getProperty('width');
        $height = $file->getProperty('height');

        $this->calculateMaxWidthAndHeight($width, $height);

        $publicUrl = $this->getLocalUrl($file);

        if (!isset($publicUrl)) {
            $publicUrl = sprintf($this->placeholderServiceUrl, $width, $height);
        }

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
    public function hash($fileIdentifier, $hashAlgorithm)
    {
        if ($this->useParentDriver($fileIdentifier)) {
            return parent::hash($fileIdentifier, $hashAlgorithm);
        }

        if (!in_array($hashAlgorithm, $this->supportedHashAlgorithms)) {
            throw new \InvalidArgumentException(
                'Hash algorithm "' . $hashAlgorithm . '" is not supported.', 1304964032
            );
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
    protected function calculateMaxWidthAndHeight(&$width, &$height)
    {
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
     * Generic wrapper for extracting a list of items from a path.
     *
     * @param string $folderIdentifier
     * @param int $start The position to start the listing; if not set, start from the beginning
     * @param int $numberOfItems The number of items to list; if set to zero, all items are returned
     * @param array $filterMethods The filter methods used to filter the directory items
     * @param bool $includeFiles
     * @param bool $includeDirs
     * @param bool $recursive
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getDirectoryItemList(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        array $filterMethods,
        $includeFiles = true,
        $includeDirs = true,
        $recursive = false,
        $sort = '',
        $sortRev = false
    ) {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        $realPath = $this->getAbsolutePath($folderIdentifier);
        if (!is_dir($realPath)) {
            return [];
        }

        return parent::getDirectoryItemList(
            $folderIdentifier,
            $start,
            $numberOfItems,
            $filterMethods,
            $includeFiles,
            $includeDirs,
            $recursive,
            $sort,
            $sortRev
        );
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\File $file
     * @return \TYPO3\CMS\Core\Resource\File
     */
    protected function getDummyFileObject($file): \TYPO3\CMS\Core\Resource\File
    {
        if (!$this->useLocalFilesIfAvailable) {
            return null;
        }

        if (empty($this->localDummyResourcePath) || !is_dir($this->localDummyResourcePath)) {
            return null;
        }

        $extension = $file->getExtension();
        $dummyFile = $this->localDummyResourcePath . $extension . '.' . $extension;

        if (file_exists($dummyFile)) {
            $dummyFileRelative = substr($dummyFile, strlen(PATH_site));
            return $this->getResourceFactory()->getFileObjectFromCombinedIdentifier($dummyFileRelative);
        }

        return null;
    }

    /**
     * Returns an instance of the FileIndexRepository
     *
     * @return \TYPO3\CMS\Core\Resource\Index\FileIndexRepository
     */
    protected function getFileIndexRepository()
    {
        return \TYPO3\CMS\Core\Resource\Index\FileIndexRepository::getInstance();
    }

    /**
     * @param string $identifier
     * @return NULL|\TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\ProcessedFile
     */
    protected function getFileObjectByIdentifier($identifier)
    {
        return $this->getResourceFactory()->getFileObjectByStorageAndIdentifier($this->storageUid, $identifier);
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\File $file
     * @return null
     */
    protected function getLocalUrl($file)
    {
        $dummyFileObject = $this->getDummyFileObject($file);

        if (isset($dummyFileObject) && $dummyFileObject->exists()) {
            return $dummyFileObject->getPublicUrl();
        } else {
            return null;
        }
    }

    /**
     *
     * @return \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected function getResourceFactory()
    {
        return \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
    }

    /**
     * Checks if this driver should be used for the given file.
     * We only use a dummy image if the real file does not exist and if the file is an image.
     *
     * @param string $fileIdentifier
     * @return bool
     */
    protected function useParentDriver($fileIdentifier)
    {
        if (file_exists($this->getAbsolutePath($fileIdentifier))) {
            return true;
        }

        $storage = $this->getResourceFactory()->getStorageObject($this->storageUid);

        if ($storage->isWithinProcessingFolder($fileIdentifier)) {
            return true;
        }

        $fileData = $this->getFileIndexRepository()->findOneByStorageUidAndIdentifier(
            $storage->getUid(),
            $fileIdentifier
        );
        if ($fileData === false) {
            return true;
        }

        $this->disableHasFileCheck = true;
        $file = $this->getResourceFactory()->getFileObjectByStorageAndIdentifier($this->storageUid, $fileIdentifier);
        $this->disableHasFileCheck = false;

        if (isset($file)) {
            $fileProperties = $file->getProperties();

            if (isset($fileProperties['type']) && (int)$fileProperties['type'] === \TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_IMAGE) {
                return false;
            }
        }

        return true;
    }
}
