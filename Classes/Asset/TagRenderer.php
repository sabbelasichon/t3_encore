<?php

declare(strict_types=1);

/*
 * This file is part of the "typo3_encore" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\Typo3Encore\Asset;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Ssch\Typo3Encore\Integration\AssetRegistryInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

final class TagRenderer implements TagRendererInterface
{
    /**
     * @var EntrypointLookupCollectionInterface
     */
    private $entrypointLookupCollection;

    /**
     * @var AssetRegistryInterface
     */
    private $assetRegistry;

    /**
     * @var ApplicationType|null
     */
    private $applicationType;

    public function __construct(EntrypointLookupCollectionInterface $entrypointLookupCollection, AssetRegistryInterface $assetRegistry)
    {
        try {
            $this->applicationType = array_key_exists('TYPO3_REQUEST', $GLOBALS) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface ? ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST']) : null;
        } catch (RuntimeException $e) {
            $this->applicationType = null;
        }

        $this->entrypointLookupCollection = $entrypointLookupCollection;
        $this->assetRegistry = $assetRegistry;
    }

    public function getWebpackScriptTags(string $entryName, string $buildName = EntrypointLookupInterface::DEFAULT_BUILD, array $parameters = [], bool $registerFile = true): array
    {
        $entryPointLookup = $this->getEntrypointLookup($buildName);

        $integrityHashes = ($entryPointLookup instanceof IntegrityDataProviderInterface) ? $entryPointLookup->getIntegrityData() : [];
        $files = $entryPointLookup->getJavaScriptFiles($entryName);

        unset($parameters['file']);

        // We do not want to replace null values in $attributes
        $parameters = array_filter($parameters, static function ($param) {
            return !is_null($param);
        });

        $files = array_map(function (string $file) use ($parameters, $registerFile): array {
            $this->addAdditionalAbsRefPrefixDirectories($file);

            $attributes = array_replace([
                'file' => $this->removeLeadingSlash($file, $parameters) ? ltrim($file, '/') : $file,
                'type' => $this->removeType($parameters) ? '' : 'text/javascript',
                'compress' => false,
                'forceOnTop' => false,
                'allWrap' => '',
                'excludeFromConcatenation' => true,
                'splitChar' => '|',
                'async' => false,
                'integrity' => $integrityHashes[$file] ?? '',
                'defer' => false,
                'crossorigin' => '',
            ], $parameters);

            if ($registerFile === true) {
                $this->assetRegistry->registerFile($file, 'script');
            }

            return $attributes;
        }, $files);

        // use filename (key=0) as index in array
        $files = array_column($files, null, 'file');

        return $files;
    }

    public function renderWebpackScriptTags(string $entryName, string $position = self::POSITION_FOOTER, string $buildName = EntrypointLookupInterface::DEFAULT_BUILD, PageRenderer $pageRenderer = null, array $parameters = [], bool $registerFile = true, bool $isLibrary = false): void
    {
        /** @var PageRenderer $pageRenderer */
        $pageRenderer = $pageRenderer ?? GeneralUtility::makeInstance(PageRenderer::class);
        $files = $this->getWebpackScriptTags($entryName, $buildName, $parameters, $registerFile);
        foreach ($files as $attributes) {
            $file = $attributes['file'];
            $attributes = array_values($attributes);

            $pageRendererMethodName = 'addJS' . ($position === self::POSITION_FOOTER ? 'Footer' : '');

            if ($isLibrary) {
                $pageRendererMethodName .= 'Library';
                $filename = basename($file);
                $pageRenderer->{$pageRendererMethodName}($filename, ...$attributes);
            } else {
                $pageRendererMethodName .= 'File';
                $pageRenderer->{$pageRendererMethodName}(...$attributes);
            }
        }
    }

    public function getWebpackLinkTags(string $entryName, string $media = 'all', string $buildName = EntrypointLookupInterface::DEFAULT_BUILD, array $parameters = [], bool $registerFile = true): array
    {
        $entryPointLookup = $this->getEntrypointLookup($buildName);
        $files = $entryPointLookup->getCssFiles($entryName);

        unset($parameters['file']);

        $files = array_map(function (string $file) use ($media, $parameters, $registerFile): array {
            $this->addAdditionalAbsRefPrefixDirectories($file);

            $attributes = array_replace([
                'file' => $this->removeLeadingSlash($file, $parameters) ? ltrim($file, '/') : $file,
                'rel' => 'stylesheet',
                'media' => $media,
                'title' => '',
                'compress' => false,
                'forceOnTop' => false,
                'allWrap' => '',
                'excludeFromConcatenation' => true,
                'splitChar' => '|',
                'inline' => false,
            ], $parameters);

            if ($registerFile === true) {
                $this->assetRegistry->registerFile($file, 'style');
            }

            return $attributes;
        }, $files);

        // use filename (key=0) as index in array
        $files = array_column($files, null, 'file');

        return $files;
    }

    public function renderWebpackLinkTags(string $entryName, string $media = 'all', string $buildName = EntrypointLookupInterface::DEFAULT_BUILD, PageRenderer $pageRenderer = null, array $parameters = [], bool $registerFile = true): void
    {
        /** @var PageRenderer $pageRenderer */
        $pageRenderer = $pageRenderer ?? GeneralUtility::makeInstance(PageRenderer::class);

        $files = $this->getWebpackLinkTags($entryName, $media, $buildName, $parameters, $registerFile);
        foreach ($files as $attributes) {
            $attributes = array_values($attributes);
            $pageRenderer->addCssFile(...$attributes);
        }
    }

    private function getEntrypointLookup(string $buildName): EntrypointLookupInterface
    {
        return $this->entrypointLookupCollection->getEntrypointLookup($buildName);
    }

    private function addAdditionalAbsRefPrefixDirectories(string $file): void
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories']) &&
            is_string($GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'])
        ) {
            $directories = GeneralUtility::trimExplode(
                ',',
                $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'],
                true
            );

            $newDir = basename(dirname($file));

            if (false === in_array($newDir, $directories, true)) {
                $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'] .= ',' . $newDir;
            }
        }
    }

    private function removeLeadingSlash($file, array $parameters): bool
    {
        if (array_key_exists('inline', $parameters) && (bool)$parameters['inline']) {
            return true;
        }

        if ($this->applicationType === null) {
            return false;
        }

        if (!$this->applicationType->isFrontend()) {
            return false;
        }

        if ($this->getTypoScriptFrontendController()->absRefPrefix === '') {
            return false;
        }

        if ($this->getTypoScriptFrontendController()->absRefPrefix === '/') {
            //avoid double //
            return true;
        }

        return !GeneralUtility::isValidUrl($file);
    }

    private function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    private function removeType(array $parameters): bool
    {
        if (array_key_exists('type', $parameters)) {
            return false;
        }

        if ($this->applicationType === null) {
            return false;
        }

        if (!$this->applicationType->isFrontend()) {
            return false;
        }

        if ($this->getTypoScriptFrontendController()->config['config']['doctype'] !== 'html5') {
            return false;
        }

        return true;
    }
}
