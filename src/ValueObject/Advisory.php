<?php

declare(strict_types=1);

namespace SecHole\ValueObject;

final readonly class Advisory
{
    public function __construct(
        private string $title,
        private string $affectedVersions,
        private ?string $cve,
        private ?string $severity,
        private ?string $link
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAffectedVersions(): string
    {
        return $this->affectedVersions;
    }

    public function getCve(): string
    {
        return $this->cve ?? '-';
    }

    public function getSeverity(): string
    {
        return $this->severity ?? 'unknown';
    }

    public function getLink(): string
    {
        return $this->link ?? '-';
    }
}
