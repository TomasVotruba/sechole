<?php

declare(strict_types=1);

namespace SecHole\Tests;

use PHPUnit\Framework\TestCase;
use SecHole\ComposerLockParser;
use SecHole\Exception\SecHoleException;
use SecHole\ValueObject\InstalledPackage;

final class ComposerLockParserTest extends TestCase
{
    /**
     * @var ComposerLockParser
     */
    private $composerLockParser;

    protected function setUp(): void
    {
        $this->composerLockParser = new ComposerLockParser();
    }

    public function testKeepsOnlyWatchedVendorsSortedByName(): void
    {
        $installedPackages = $this->composerLockParser->parse(__DIR__ . '/Fixture/composer.lock');

        $packageNames = array_map(function (InstalledPackage $installedPackage): string {
            return $installedPackage->getName();
        }, $installedPackages);

        $this->assertSame([
            'doctrine/dbal',
            'illuminate/database',
            'symfony/http-foundation',
            'symfony/http-kernel',
            'twig/twig',
        ], $packageNames);
    }

    public function testStripsVersionPrefix(): void
    {
        $installedPackages = $this->composerLockParser->parse(__DIR__ . '/Fixture/composer.lock');

        $versionsByName = [];
        foreach ($installedPackages as $installedPackage) {
            $versionsByName[$installedPackage->getName()] = $installedPackage->getVersion();
        }

        $this->assertSame('4.4.0', $versionsByName['symfony/http-kernel']);
        $this->assertSame('2.10.0', $versionsByName['doctrine/dbal']);
    }

    public function testAcceptsDirectoryHoldingComposerLock(): void
    {
        $fromDirectory = $this->composerLockParser->parse(__DIR__ . '/Fixture');
        $fromFile = $this->composerLockParser->parse(__DIR__ . '/Fixture/composer.lock');

        $this->assertEquals($fromFile, $fromDirectory);
    }

    public function testAcceptsDirectoryWithTrailingSlash(): void
    {
        $installedPackages = $this->composerLockParser->parse(__DIR__ . '/Fixture/');

        $this->assertCount(5, $installedPackages);
    }

    public function testThrowsOnMissingFile(): void
    {
        $this->expectException(SecHoleException::class);
        $this->expectExceptionMessage('File "missing.lock" was not found');

        $this->composerLockParser->parse('missing.lock');
    }

    public function testThrowsOnDirectoryWithoutComposerLock(): void
    {
        $this->expectException(SecHoleException::class);
        $this->expectExceptionMessage('No composer.lock found in');

        $this->composerLockParser->parse(__DIR__ . '/Fixture/EmptyProject');
    }
}
