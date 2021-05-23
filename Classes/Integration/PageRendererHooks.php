<?php

declare(strict_types=1);

/*
 * This file is part of the "typo3_encore" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\Typo3Encore\Integration;

use Ssch\Typo3Encore\Asset\EntrypointLookupInterface;
use Ssch\Typo3Encore\Asset\TagRenderer;
use Ssch\Typo3Encore\Asset\TagRendererInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

final class PageRendererHooks
{
    /**
     * @var string
     */
    private const ENCORE_PREFIX = 'typo3_encore:';

    /**
     * @var int
     */
    private const PART_FOOTER = 2;

    /**
     * @var TagRendererInterface
     */
    private $tagRenderer;

    public function __construct(TagRendererInterface $tagRenderer)
    {
        $this->tagRenderer = $tagRenderer;
    }

    public function renderPreProcess(array $params, PageRenderer $pageRenderer): void
    {
        // At this point, TYPO3 provides all javascript includes in only 'Files' or 'Libs'
        foreach (TagRenderer::ALLOWED_JS_POSITIONS as $includeType) {
            if (empty($params[$includeType])) {
                continue;
            }

            $includes = [];
            foreach ($params[$includeType] as $key => $jsFile) {
                if (! $this->isEncoreEntryName($jsFile['file'])) {
                    $includes[$key] = $jsFile;
                    continue;
                }

                $buildAndEntryName = $this->createBuildAndEntryName($jsFile['file']);
                $buildName = EntrypointLookupInterface::DEFAULT_BUILD;

                if (count($buildAndEntryName) === 2) {
                    [$buildName, $entryName] = $buildAndEntryName;
                } else {
                    $entryName = $buildAndEntryName[0];
                }

                unset($jsFile['file'], $jsFile['integrity']);

                $files = $this->tagRenderer->getWebpackScriptTags($entryName, $buildName, $jsFile, true);
                foreach ($files as $key => $jsFile) {
                    $includes[$key] = $jsFile;
                }
            }
            $params[$includeType] = $includes;
        }

        // Add CSS-Files by entryNames
        foreach (TagRenderer::ALLOWED_CSS_POSITIONS as $includeType) {
            if (empty($params[$includeType])) {
                continue;
            }

            $includes = [];
            foreach ($params[$includeType] as $key => $cssFile) {
                if (! $this->isEncoreEntryName($cssFile['file'])) {
                    $includes[$key] = $cssFile;
                    continue;
                }
                $buildAndEntryName = $this->createBuildAndEntryName($cssFile['file']);
                $buildName = EntrypointLookupInterface::DEFAULT_BUILD;

                if (count($buildAndEntryName) === 2) {
                    [$buildName, $entryName] = $buildAndEntryName;
                } else {
                    $entryName = $buildAndEntryName[0];
                }

                unset($cssFile['file']);

                $files = $this->tagRenderer->getWebpackLinkTags($entryName, 'all', $buildName, $cssFile);
                foreach ($files as $key => $cssFile) {
                    $includes[$key] = $cssFile;
                }
            }
            $params[$includeType] = $includes;
        }
    }

    private function isEncoreEntryName(string $file): bool
    {
        return StringUtility::beginsWith($file, self::ENCORE_PREFIX);
    }

    private function removePrefix(string $file): string
    {
        return str_replace(self::ENCORE_PREFIX, '', $file);
    }

    private function createBuildAndEntryName(string $file): array
    {
        return GeneralUtility::trimExplode(':', $this->removePrefix($file), true, 2);
    }
}
