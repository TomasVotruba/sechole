<?php

declare(strict_types=1);

namespace SecHole\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SecHole\AdvisoryMatcher;
use SecHole\ValueObject\Advisory;

final class AdvisoryMatcherTest extends TestCase
{
    private AdvisoryMatcher $advisoryMatcher;

    protected function setUp(): void
    {
        $this->advisoryMatcher = new AdvisoryMatcher();
    }

    #[DataProvider('provideFilterForVersion')]
    public function testFilterForVersion(string $version, int $expectedCount): void
    {
        $advisories = [
            $this->createAdvisory('>=4.0.0,<4.4.10'),
            $this->createAdvisory('>=4.0.0,<4.4.50|>=5.0.0,<5.4.20'),
        ];

        $matchedAdvisories = $this->advisoryMatcher->filterForVersion($advisories, $version);

        $this->assertCount($expectedCount, $matchedAdvisories);
    }

    public static function provideFilterForVersion(): iterable
    {
        yield 'both constraints hit' => ['4.4.0', 2];
        yield 'only the wider constraint hits' => ['4.4.20', 1];
        yield 'other major branch hits' => ['5.4.0', 1];
        yield 'patched version is clean' => ['4.4.50', 0];
    }

    public function testRecommendsLowestCleanVersion(): void
    {
        $advisories = [$this->createAdvisory('>=4.0.0,<4.4.50')];

        [$recommendedVersion, $advisoryCount] = $this->advisoryMatcher->resolveBestVersion(
            '4.4.0',
            $advisories,
            ['4.4.0', '4.4.20', '4.4.50', '4.4.60', '5.4.0']
        );

        $this->assertSame('4.4.50', $recommendedVersion);
        $this->assertSame(0, $advisoryCount);
    }

    public function testRecommendsVersionWithFewerAdvisoriesWhenNoneIsClean(): void
    {
        $advisories = [
            $this->createAdvisory('>=4.0.0,<4.4.50'),
            $this->createAdvisory('>=4.0.0,<6.0.0'),
        ];

        [$recommendedVersion, $advisoryCount] = $this->advisoryMatcher->resolveBestVersion(
            '4.4.0',
            $advisories,
            ['4.4.20', '4.4.50', '5.4.0']
        );

        $this->assertSame('4.4.50', $recommendedVersion);
        $this->assertSame(1, $advisoryCount);
    }

    public function testReturnsNullWhenEveryHigherVersionIsAffected(): void
    {
        $advisories = [$this->createAdvisory('>=4.0.0')];

        [$recommendedVersion, $advisoryCount] = $this->advisoryMatcher->resolveBestVersion(
            '4.4.0',
            $advisories,
            ['4.4.20', '5.4.0', '6.0.0']
        );

        $this->assertNull($recommendedVersion);
        $this->assertSame(1, $advisoryCount);
    }

    public function testIgnoresVersionsBelowInstalledOne(): void
    {
        $advisories = [$this->createAdvisory('>=4.4.0,<4.4.50')];

        [$recommendedVersion, $advisoryCount] = $this->advisoryMatcher->resolveBestVersion(
            '4.4.10',
            $advisories,
            ['4.3.0', '4.4.0', '4.4.50']
        );

        $this->assertSame('4.4.50', $recommendedVersion);
        $this->assertSame(0, $advisoryCount);
    }

    private function createAdvisory(string $affectedVersions): Advisory
    {
        return new Advisory('Some advisory', $affectedVersions, null, null, null);
    }
}
