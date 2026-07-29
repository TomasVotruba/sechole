<?php

declare(strict_types=1);

namespace SecHole\ValueObject;

final class Advisory
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $affectedVersions;

    /**
     * @var string|null
     */
    private $cve;

    /**
     * @var string|null
     */
    private $severity;

    /**
     * @var string|null
     */
    private $link;

    public function __construct(
        string $title,
        string $affectedVersions,
        ?string $cve,
        ?string $severity,
        ?string $link
    ) {
        $this->title = $title;
        $this->affectedVersions = $affectedVersions;
        $this->cve = $cve;
        $this->severity = $severity;
        $this->link = $link;
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
