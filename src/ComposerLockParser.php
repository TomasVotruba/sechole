<?php

declare(strict_types=1);

namespace SecHole;

use SecHole\Exception\SecHoleException;
use SecHole\ValueObject\InstalledPackage;

final class ComposerLockParser
{
    /**
     * @var string[]
     */
    private const WATCHED_VENDORS = ['symfony', 'twig', 'doctrine', 'illuminate'];

    /**
     * @return InstalledPackage[]
     */
    public function parse(string $composerLockFilePath): array
    {
        if (! is_file($composerLockFilePath)) {
            throw new SecHoleException(sprintf('File "%s" was not found', $composerLockFilePath));
        }

        $fileContents = (string) file_get_contents($composerLockFilePath);

        $json = json_decode($fileContents, true);
        if (! is_array($json) || json_last_error() !== JSON_ERROR_NONE) {
            throw new SecHoleException(sprintf('File "%s" is not a valid composer.lock', $composerLockFilePath));
        }

        $packageItems = array_merge(
            isset($json['packages']) ? $json['packages'] : [],
            isset($json['packages-dev']) ? $json['packages-dev'] : []
        );

        $installedPackages = [];
        foreach ($packageItems as $packageItem) {
            $name = (string) $packageItem['name'];
            if (! $this->isWatched($name)) {
                continue;
            }

            $installedPackages[$name] = new InstalledPackage($name, ltrim((string) $packageItem['version'], 'v'));
        }

        ksort($installedPackages);

        return array_values($installedPackages);
    }

    private function isWatched(string $packageName): bool
    {
        $vendor = strstr($packageName, '/', true);

        return in_array($vendor, self::WATCHED_VENDORS, true);
    }
}
