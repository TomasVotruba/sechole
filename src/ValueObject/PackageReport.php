<?php

declare(strict_types=1);

namespace SecHole\ValueObject;

final class PackageReport
{
    /**
     * @var InstalledPackage
     */
    private $installedPackage;

    /**
     * @var Advisory[]
     */
    private $advisories;

    /**
     * @var MinorBranchReport[]
     */
    private $minorBranchReports;

    /**
     * @param Advisory[] $advisories
     * @param MinorBranchReport[] $minorBranchReports
     */
    public function __construct(
        InstalledPackage $installedPackage,
        array $advisories,
        array $minorBranchReports
    ) {
        $this->installedPackage = $installedPackage;
        $this->advisories = $advisories;
        $this->minorBranchReports = $minorBranchReports;
    }

    public function getPackageName(): string
    {
        return $this->installedPackage->getName();
    }

    public function getInstalledVersion(): string
    {
        return $this->installedPackage->getVersion();
    }

    /**
     * @return Advisory[]
     */
    public function getAdvisories(): array
    {
        return $this->advisories;
    }

    public function getAdvisoryCount(): int
    {
        return count($this->advisories);
    }

    /**
     * @return MinorBranchReport[]
     */
    public function getMinorBranchReports(): array
    {
        return $this->minorBranchReports;
    }

    public function isClean(): bool
    {
        return $this->advisories === [];
    }
}
