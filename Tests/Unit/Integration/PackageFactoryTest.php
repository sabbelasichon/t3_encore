<?php

namespace Ssch\Typo3Encore\Tests\Unit\Integration;

TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase

use PHPUnit\Framework\MockObject\MockObject;
use Ssch\Typo3Encore\Integration\FilesystemInterface;
use Ssch\Typo3Encore\Integration\PackageFactory;
use Ssch\Typo3Encore\Integration\SettingsServiceInterface;
use Symfony\Component\Asset\PackageInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @covers \Ssch\Typo3Encore\Integration\PackageFactory
 */
final class PackageFactoryTest extends UnitTestCase
{
    /**
     * @var PackageFactory
     */
    protected $subject;

    /**
     * @var MockObject|SettingsServiceInterface
     */
    protected $settingsService;

    /**
     * @var FilesystemInterface|MockObject
     */
    protected $filesystem;

    protected function setUp(): void
    {
        $this->settingsService = $this->getMockBuilder(SettingsServiceInterface::class)->getMock();
        $this->filesystem = $this->getMockBuilder(FilesystemInterface::class)->getMock();
        $this->subject = new PackageFactory($this->settingsService, $this->filesystem);
    }

    /**
     * @test
     */
    public function returnsPackageWithDefaultManifestPath(): void
    {
        $this->settingsService->method('getStringByPath')->with('manifestJsonPath')->willReturn('manifest.json');
        $this->filesystem->method('getFileAbsFileName')->with('manifest.json')->willReturn('manifest.json');
        $this->assertInstanceOf(PackageInterface::class, $this->subject->getPackage('_default'));
    }

    /**
     * @test
     */
    public function returnsPackageWithSpecificManifestPath(): void
    {
        $this->settingsService->method('getStringByPath')->with('packages.custom.manifestJsonPath')->willReturn('manifest.json');
        $this->filesystem->method('getFileAbsFileName')->with('manifest.json')->willReturn('manifest.json');
        $this->assertInstanceOf(PackageInterface::class, $this->subject->getPackage('custom'));
    }
}
