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

            // Is the include type 'jsLibs' and should be treated as a library
            $isLibrary = $includeType === TagRenderer::POSITION_JS_LIBRARY;

            foreach ($params[$includeType] as $key => $jsFile) {
                if (! $this->isEncoreEntryName($jsFile['file'])) {
                    continue;
                }

                $buildAndEntryName = $this->createBuildAndEntryName($jsFile['file']);
                $buildName = EntrypointLookupInterface::DEFAULT_BUILD;

                if (count($buildAndEntryName) === 2) {
                    [$buildName, $entryName] = $buildAndEntryName;
                } else {
                    $entryName = $buildAndEntryName[0];
                }

                $position = ($jsFile['section'] ?? '') === self::PART_FOOTER ? TagRenderer::POSITION_FOOTER : '';

                unset($params[$includeType][$key], $jsFile['file'], $jsFile['section'], $jsFile['integrity']);

                $this->tagRenderer->renderWebpackScriptTags($entryName, $position, $buildName, $pageRenderer, $jsFile, true, $isLibrary);
            }
        }

        // Add CSS-Files by entryNames
        foreach (TagRenderer::ALLOWED_CSS_POSITIONS as $includeType) {
            if (empty($params[$includeType])) {
                continue;
            }

            foreach ($params[$includeType] as $key => $cssFile) {
                if (! $this->isEncoreEntryName($cssFile['file'])) {
                    continue;
                }
                $buildAndEntryName = $this->createBuildAndEntryName($cssFile['file']);
                $buildName = EntrypointLookupInterface::DEFAULT_BUILD;

                if (count($buildAndEntryName) === 2) {
                    [$buildName, $entryName] = $buildAndEntryName;
                } else {
                    $entryName = $buildAndEntryName[0];
                }

                unset($params[$includeType][$key], $cssFile['file']);

                $this->tagRenderer->renderWebpackLinkTags($entryName, 'all', $buildName, $pageRenderer, $cssFile);
            }
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
