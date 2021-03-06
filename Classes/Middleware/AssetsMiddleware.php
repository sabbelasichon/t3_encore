<?php

declare(strict_types=1);

/*
 * This file is part of the "typo3_encore" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\Typo3Encore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ssch\Typo3Encore\Integration\AssetRegistryInterface;
use Ssch\Typo3Encore\Integration\SettingsServiceInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\HttpHeaderSerializer;
use Symfony\Component\WebLink\Link;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

final class AssetsMiddleware implements MiddlewareInterface
{
    /**
     * @var array
     */
    private static $crossOriginAllowed = ['preload', 'preconnect'];

    /**
     * @var TypoScriptFrontendController
     */
    protected $controller;

    /**
     * @var AssetRegistryInterface
     */
    private $assetRegistry;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    public function __construct(AssetRegistryInterface $assetRegistry, SettingsServiceInterface $settingsService)
    {
        $this->controller = $GLOBALS['TSFE'];
        $this->settingsService = $settingsService;
        $this->assetRegistry = $assetRegistry;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (($response instanceof NullResponse)) {
            return $response;
        }

        $registeredFiles = $this->collectRegisteredFiles();
        if ($registeredFiles === []) {
            return $response;
        }

        if (null === $linkProvider = $request->getAttribute('_links')) {
            $request = $request->withAttribute('_links', new GenericLinkProvider());
        }

        /** @var GenericLinkProvider $linkProvider */
        $linkProvider = $request->getAttribute('_links');
        $defaultAttributes = $this->collectDefaultAttributes();
        $crossOrigin = $defaultAttributes['crossorigin'] ? (bool)$defaultAttributes['crossorigin'] : false;

        foreach ($registeredFiles as $rel => $relFiles) {
            // You can disable or enable one of the resource hints via typoscript simply by adding something like that preload.enable = 1, dns-prefetch.enable = 1
            if ($this->getBooleanConfigByPath(sprintf('%s.enable', $rel)) === false) {
                continue;
            }

            foreach ($relFiles['files'] as $type => $files) {
                foreach ($files as $href => $attributes) {
                    $link = (new Link($rel, PathUtility::getAbsoluteWebPath($href)))->withAttribute('as', $type);
                    if ($this->canAddCrossOriginAttribute($crossOrigin, $rel)) {
                        $link = $link->withAttribute('crossorigin', $crossOrigin);
                    }

                    foreach ($attributes as $key => $value) {
                        $link = $link->withAttribute($key, $value);
                    }

                    $linkProvider = $linkProvider->withLink($link);
                }
            }
        }

        $request = $request->withAttribute('_links', $linkProvider);

        /** @var GenericLinkProvider $linkProvider */
        $linkProvider = $request->getAttribute('_links');

        if ($linkProvider->getLinks() !== []) {
            $response = $response->withHeader('Link', (new HttpHeaderSerializer())->serialize($linkProvider->getLinks()));
        }

        return $response;
    }

    private function canAddCrossOriginAttribute(bool $crossOrigin, string $rel): bool
    {
        return false !== $crossOrigin && '' !== (string)$crossOrigin && in_array($rel, self::$crossOriginAllowed, true);
    }

    private function collectRegisteredFiles(): array
    {
        return array_replace(
            $this->controller->config['encore_asset_registry']['registered_files'] ?? [],
            $this->assetRegistry->getRegisteredFiles()
        );
    }

    private function collectDefaultAttributes(): array
    {
        return array_replace(
            $this->controller->config['encore_asset_registry']['default_attributes'] ?? [],
            $this->assetRegistry->getDefaultAttributes()
        );
    }

    private function getBooleanConfigByPath(string $path): bool
    {
        if ($this->settingsService->getSettings() !== []) {
            return $this->settingsService->getBooleanByPath($path);
        }

        $cachedSettings = $this->controller->config['encore_asset_registry']['settings'] ?? [];

        return (bool)ObjectAccess::getPropertyPath($cachedSettings, $path);
    }
}
