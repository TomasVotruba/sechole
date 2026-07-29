<?php

declare(strict_types=1);

namespace SecHole\ValueObject;

/**
 * The latest release of a single minor branch, e.g. 3.1.10 for the 3.1 branch,
 * together with the number of advisories still affecting it.
 */
final class MinorBranchReport
{
    /**
     * @var string
     */
    private $minorBranch;

    /**
     * @var string
     */
    private $latestVersion;

    /**
     * @var int
     */
    private $advisoryCount;

    /**
     * @var string
     */
    private $releasedAt;

    public function __construct(
        string $minorBranch,
        string $latestVersion,
        int $advisoryCount,
        string $releasedAt
    ) {
        $this->minorBranch = $minorBranch;
        $this->latestVersion = $latestVersion;
        $this->advisoryCount = $advisoryCount;
        $this->releasedAt = $releasedAt;
    }

    public function getMinorBranch(): string
    {
        return $this->minorBranch;
    }

    public function getLatestVersion(): string
    {
        return $this->latestVersion;
    }

    public function getAdvisoryCount(): int
    {
        return $this->advisoryCount;
    }

    public function getReleasedAt(): string
    {
        return $this->releasedAt;
    }

    public function isClean(): bool
    {
        return $this->advisoryCount === 0;
    }
}
