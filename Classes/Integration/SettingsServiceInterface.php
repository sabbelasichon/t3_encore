<?php


namespace Ssch\Typo3Encore\Integration;

/**
 * This file is part of the "typo3_encore" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

interface SettingsServiceInterface
{
    public function getSettings(): array;

    public function getArrayByPath(string $path): array;

    public function getStringByPath(string $path): string;

    public function getBooleanByPath(string $path): bool;
}
