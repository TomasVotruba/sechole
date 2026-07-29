<?php

declare(strict_types=1);

namespace SecHole;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use SecHole\ValueObject\Advisory;
use SecHole\ValueObject\MinorBranchReport;

final class AdvisoryMatcher
{
    /**
     * @param Advisory[] $advisories
     * @return Advisory[]
     */
    public function filterForVersion(array $advisories, string $version): array
    {
        $matchedAdvisories = array_filter($advisories, function (Advisory $advisory) use ($version): bool {
            return Semver::satisfies($version, $advisory->getAffectedVersions());
        });

        return array_values($matchedAdvisories);
    }

    /**
     * Every minor branch above the installed version, represented by its latest release,
     * so 2.8 installed lists 2.8, 3.0, 3.1, 3.2... each with its own advisory count.
     *
     * @param Advisory[] $advisories
     * @param string[] $candidateVersions
     * @return MinorBranchReport[] ascending by branch
     */
    public function resolveMinorBranches(
        string $installedVersion,
        array $advisories,
        array $candidateVersions
    ): array {
        $latestVersionByMinorBranch = [];

        foreach ($candidateVersions as $candidateVersion) {
            if (! Comparator::greaterThan($candidateVersion, $installedVersion)) {
                continue;
            }

            $minorBranch = $this->resolveMinorBranch($candidateVersion);

            $isNewer = ! isset($latestVersionByMinorBranch[$minorBranch])
                || Comparator::greaterThan($candidateVersion, $latestVersionByMinorBranch[$minorBranch]);

            if ($isNewer) {
                $latestVersionByMinorBranch[$minorBranch] = $candidateVersion;
            }
        }

        uasort($latestVersionByMinorBranch, function (string $left, string $right): int {
            return version_compare($left, $right);
        });

        $minorBranchReports = [];
        foreach ($latestVersionByMinorBranch as $minorBranch => $latestVersion) {
            $minorBranchReports[] = new MinorBranchReport(
                (string) $minorBranch,
                $latestVersion,
                count($this->filterForVersion($advisories, $latestVersion))
            );
        }

        return $minorBranchReports;
    }

    private function resolveMinorBranch(string $version): string
    {
        $versionParts = explode('.', $version);

        return $versionParts[0] . '.' . (isset($versionParts[1]) ? $versionParts[1] : '0');
    }
}
