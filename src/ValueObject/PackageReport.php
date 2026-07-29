<?php

declare(strict_types=1);

namespace SecHole\ValueObject;

final readonly class PackageReport
{
    /**
     * @param Advisory[] $advisories
     */
    public function __construct(
        private InstalledPackage $installedPackage,
        private array $advisories,
        private ?string $recommendedVersion,
        private int $recommendedVersionAdvisoryCount
    ) {
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
