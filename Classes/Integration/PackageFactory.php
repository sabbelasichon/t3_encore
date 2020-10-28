<?php
declare(strict_types = 1);

namespace Ssch\Typo3Encore\Integration;

/**
 * This file is part of the "typo3_encore" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;

final class PackageFactory implements PackageFactoryInterface
{

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    public function __construct(SettingsServiceInterface $settingsService, FilesystemInterface $filesystem)
    {
        $this->settingsService = $settingsService;
        $this->filesystem = $filesystem;
    }

    public function getPackage(string $package): PackageInterface
    {
        $manifestJsonPath = $package === '_default' ? 'manifestJsonPath' : sprintf('packages.%s.manifestJsonPath', $package);
        return new Package(new JsonManifestVersionStrategy($this->filesystem->getFileAbsFileName($this->settingsService->getStringByPath($manifestJsonPath))));
    }
}
