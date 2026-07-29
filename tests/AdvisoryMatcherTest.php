<?php

declare(strict_types=1);

namespace SecHole\Tests;

use PHPUnit\Framework\TestCase;
use SecHole\AdvisoryMatcher;
use SecHole\ValueObject\Advisory;
use SecHole\ValueObject\MinorBranchReport;

final class AdvisoryMatcherTest extends TestCase
{
    /**
     * @var AdvisoryMatcher
     */
    private $advisoryMatcher;

    protected function setUp(): void
    {
        $this->advisoryMatcher = new AdvisoryMatcher();
    }

    /**
     * @dataProvider provideFilterForVersion
     */
    public function testFilterForVersion(string $version, int $expectedCount): void
    {
        $advisories = [
            $this->createAdvisory('>=4.0.0,<4.4.10'),
            $this->createAdvisory('>=4.0.0,<4.4.50|>=5.0.0,<5.4.20'),
        ];

        $matchedAdvisories = $this->advisoryMatcher->filterForVersion($advisories, $version);

        $this->assertCount($expectedCount, $matchedAdvisories);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public function provideFilterForVersion(): iterable
    {
        yield 'both constraints hit' => ['4.4.0', 2];
        yield 'only the wider constraint hits' => ['4.4.20', 1];
        yield 'other major branch hits' => ['5.4.0', 1];
        yield 'patched version is clean' => ['4.4.50', 0];
    }

    public function testListsEveryMinorBranchAboveInstalledVersion(): void
    {
        $advisories = [$this->createAdvisory('>=2.0.0,<3.2.0')];

        $minorBranchReports = $this->advisoryMatcher->resolveMinorBranches(
            '2.8.0',
            $advisories,
            $this->createReleaseDates(['2.8.1', '2.8.20', '3.0.0', '3.0.9', '3.1.0', '3.2.0', '3.2.5'])
        );

        $this->assertSame(
            ['2.8', '3.0', '3.1', '3.2'],
            $this->resolveMinorBranches($minorBranchReports)
        );
    }

    public function testRepresentsEachBranchByItsLatestRelease(): void
    {
        $minorBranchReports = $this->advisoryMatcher->resolveMinorBranches(
            '2.8.0',
            [],
            $this->createReleaseDates(['2.8.1', '2.8.20', '2.8.3', '3.0.9', '3.0.0'])
        );

        $latestVersions = array_map(function (MinorBranchReport $minorBranchReport): string {
            return $minorBranchReport->getLatestVersion();
        }, $minorBranchReports);

        $this->assertSame(['2.8.20', '3.0.9'], $latestVersions);
    }

    public function testCountsAdvisoriesPerBranch(): void
    {
        $advisories = [
            $this->createAdvisory('>=2.0.0,<3.1.0'),
            $this->createAdvisory('>=2.0.0,<3.0.0'),
        ];

        $minorBranchReports = $this->advisoryMatcher->resolveMinorBranches(
            '2.8.0',
            $advisories,
            $this->createReleaseDates(['2.8.20', '3.0.9', '3.1.0'])
        );

        $advisoryCounts = array_map(function (MinorBranchReport $minorBranchReport): int {
            return $minorBranchReport->getAdvisoryCount();
        }, $minorBranchReports);

        $this->assertSame([2, 1, 0], $advisoryCounts);
        $this->assertTrue($minorBranchReports[2]->isClean());
    }

    public function testSkipsVersionsBelowOrEqualToInstalledOne(): void
    {
        $minorBranchReports = $this->advisoryMatcher->resolveMinorBranches(
            '2.8.10',
            [],
            $this->createReleaseDates(['2.6.0', '2.7.9', '2.8.10', '2.8.11'])
        );

        $this->assertSame(['2.8'], $this->resolveMinorBranches($minorBranchReports));
        $this->assertSame('2.8.11', $minorBranchReports[0]->getLatestVersion());
    }

    public function testReturnsNothingWhenNoNewerReleaseExists(): void
    {
        $minorBranchReports = $this->advisoryMatcher->resolveMinorBranches(
            '5.0.0',
            [],
            $this->createReleaseDates(['4.4.0', '5.0.0'])
        );

        $this->assertSame([], $minorBranchReports);
    }

    public function testCarriesReleaseDateOfTheLatestRelease(): void
    {
        $minorBranchReports = $this->advisoryMatcher->resolveMinorBranches(
            '2.8.0',
            [],
            ['2.8.1' => '2019-03-04', '2.8.20' => '2021-11-30', '3.0.0' => '2020-06-15']
        );

        $this->assertSame('2021-11-30', $minorBranchReports[0]->getReleasedAt());
        $this->assertSame('2020-06-15', $minorBranchReports[1]->getReleasedAt());
    }

    public function testFallsBackWhenReleaseDateIsUnknown(): void
    {
        $minorBranchReports = $this->advisoryMatcher->resolveMinorBranches('2.8.0', [], ['3.0.0' => '-']);

        $this->assertSame('-', $minorBranchReports[0]->getReleasedAt());
    }

    /**
     * @param MinorBranchReport[] $minorBranchReports
     * @return string[]
     */
    private function resolveMinorBranches(array $minorBranchReports): array
    {
        return array_map(function (MinorBranchReport $minorBranchReport): string {
            return $minorBranchReport->getMinorBranch();
        }, $minorBranchReports);
    }

    /**
     * @param string[] $versions
     * @return array<string, string>
     */
    private function createReleaseDates(array $versions): array
    {
        $releaseDatesByVersion = [];
        foreach ($versions as $version) {
            $releaseDatesByVersion[$version] = '2020-01-01';
        }

        return $releaseDatesByVersion;
    }

    private function createAdvisory(string $affectedVersions): Advisory
    {
        return new Advisory('Some advisory', $affectedVersions, null, null, null);
    }
}
