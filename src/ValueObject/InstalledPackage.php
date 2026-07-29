<?php

declare(strict_types=1);

namespace SecHole\ValueObject;

final class InstalledPackage
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $version;

    public function __construct(string $name, string $version)
    {
        $this->name = $name;
        $this->version = $version;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
