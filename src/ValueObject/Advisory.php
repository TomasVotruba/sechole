<?php

declare(strict_types=1);

namespace SecHole\ValueObject;

final class Advisory
{
    /**
     * @var string
     */
    private $affectedVersions;

    public function __construct(string $affectedVersions)
    {
        $this->affectedVersions = $affectedVersions;
    }

    public function getAffectedVersions(): string
    {
        return $this->affectedVersions;
    }
}
