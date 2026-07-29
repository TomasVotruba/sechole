<?php

declare(strict_types=1);

namespace SecHole;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use SecHole\ValueObject\Advisory;

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
     * Pick the lowest candidate version above the installed one that carries fewer advisories.
     *
     * @param Advisory[] $advisories
     * @param string[] $candidateVersions ascending
     * @return array{0: string|null, 1: int} recommended version and its advisory count
     */
    public function resolveBestVersion(
        string $installedVersion,
        array $advisories,
        array $candidateVersions
    ): array {
        $bestVersion = null;
        $bestCount = count($this->filterForVersion($advisories, $installedVersion));

        foreach ($candidateVersions as $candidateVersion) {
            if (! Comparator::greaterThan($candidateVersion, $installedVersion)) {
                continue;
            }

            $advisoryCount = count($this->filterForVersion($advisories, $candidateVersion));
            if ($advisoryCount >= $bestCount) {
                continue;
            }

            $bestVersion = $candidateVersion;
            $bestCount = $advisoryCount;

            // nothing better than zero known vulnerabilities
            if ($advisoryCount === 0) {
                break;
            }
        }

        return [$bestVersion, $bestCount];
    }
}
