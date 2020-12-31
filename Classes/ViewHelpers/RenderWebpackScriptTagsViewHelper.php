<?php

declare(strict_types=1);

/*
 * This file is part of the "typo3_encore" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\Typo3Encore\ViewHelpers;

use Ssch\Typo3Encore\Asset\EntrypointLookupInterface;
use Ssch\Typo3Encore\Asset\TagRendererInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class RenderWebpackScriptTagsViewHelper extends AbstractViewHelper
{

    /**
     * @var TagRendererInterface
     */
    private $tagRenderer;

    public function __construct(TagRendererInterface $tagRenderer)
    {
        $this->tagRenderer = $tagRenderer;
    }

    public function initializeArguments(): void
    {
        $this->registerArgument('entryName', 'string', 'The entry name', true);
        $this->registerArgument('position', 'string', 'The position to render the files', false, 'jsFooterFiles');
        $this->registerArgument('buildName', 'string', 'The build name', false, EntrypointLookupInterface::DEFAULT_BUILD);
        $this->registerArgument('parameters', 'array', 'Additional parameters for the PageRenderer', false, []);
        $this->registerArgument('registerFile', 'bool', 'Register file for HTTP/2 push functionality', false, true);
        $this->registerArgument('isLibrary', 'bool', 'Defines if the entry should be loaded as a library (i.e. before other files)', false, false);
    }

    public function render(): void
    {
        $this->tagRenderer->renderWebpackScriptTags(
            $this->arguments['entryName'],
            $this->arguments['position'],
            $this->arguments['buildName'],
            null,
            $this->arguments['parameters'],
            $this->arguments['registerFile'],
            $this->arguments['isLibrary']
        );
    }
}
