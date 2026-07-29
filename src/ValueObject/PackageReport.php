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
     * @var string|null
     */
    private $recommendedVersion;

    /**
     * @var int
     */
    private $recommendedVersionAdvisoryCount;

    /**
     * @param Advisory[] $advisories
     */
    public function __construct(
        InstalledPackage $installedPackage,
        array $advisories,
        ?string $recommendedVersion,
        int $recommendedVersionAdvisoryCount
    ) {
        $this->installedPackage = $installedPackage;
        $this->advisories = $advisories;
        $this->recommendedVersion = $recommendedVersion;
        $this->recommendedVersionAdvisoryCount = $recommendedVersionAdvisoryCount;
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

    public function getRecommendedVersion(): ?string
    {
        return $this->recommendedVersion;
    }

    public function getRecommendedVersionAdvisoryCount(): int
    {
        return $this->recommendedVersionAdvisoryCount;
    }

    public function isClean(): bool
    {
        return $this->advisories === [];
    }
}
