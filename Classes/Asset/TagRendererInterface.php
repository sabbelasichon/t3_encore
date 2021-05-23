<?php

/*
 * This file is part of the "typo3_encore" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\Typo3Encore\Asset;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;

interface TagRendererInterface extends SingletonInterface
{
    /**
     * @var array
     */
    public const ALLOWED_CSS_POSITIONS = [
        'cssLibs',
        'cssFiles'
    ];

    /**
     * @var array
     */
    public const ALLOWED_JS_POSITIONS = [
        'jsLibs',
        'jsFiles'
    ];

    /**
     * @var string
     */
    public const POSITION_FOOTER = 'footer';

    /**
     * @var string
     */
    public const POSITION_JS_LIBRARY = 'jsLibs';

    public function getWebpackScriptTags(string $entryName, string $buildName = EntrypointLookupInterface::DEFAULT_BUILD, array $parameters = [], bool $registerFile = true): array;

    public function renderWebpackScriptTags(string $entryName, string $position = 'footer', string $buildName = EntrypointLookupInterface::DEFAULT_BUILD, PageRenderer $pageRenderer = null, array $parameters = [], bool $registerFile = true, bool $isLibrary = false);

    public function getWebpackLinkTags(string $entryName, string $media = 'all', string $buildName = EntrypointLookupInterface::DEFAULT_BUILD, array $parameters = [], bool $registerFile = true): array;

    public function renderWebpackLinkTags(string $entryName, string $media = 'all', string $buildName = EntrypointLookupInterface::DEFAULT_BUILD, PageRenderer $pageRenderer = null, array $parameters = [], bool $registerFile = true);
}
