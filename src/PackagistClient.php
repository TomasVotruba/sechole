<?php

declare(strict_types=1);

namespace SecHole;

use Composer\Semver\VersionParser;
use SecHole\Exception\SecHoleException;
use SecHole\ValueObject\Advisory;

final readonly class PackagistClient
{
    private const string ADVISORIES_URL = 'https://packagist.org/api/security-advisories/';

    private const string VERSIONS_URL = 'https://repo.packagist.org/p2/%s.json';

    private const string USER_AGENT = 'sechole/1.0 (+https://packagist.org)';

    public function __construct(
        private VersionParser $versionParser
    ) {
    }

    /**
     * @param string[] $packageNames
     * @return array<string, Advisory[]> package name => advisories
     */
    public function fetchAdvisories(array $packageNames): array
    {
        if ($packageNames === []) {
            return [];
        }

        $postFields = http_build_query([
            'packages' => array_values($packageNames),
        ]);

        $response = $this->request(self::ADVISORIES_URL, $postFields);
        $advisoryItemsByPackageName = $response['advisories'] ?? [];

        $advisoriesByPackageName = [];
        foreach ($advisoryItemsByPackageName as $packageName => $advisoryItems) {
            foreach ($advisoryItems as $advisoryItem) {
                $advisoriesByPackageName[$packageName][] = new Advisory(
                    (string) ($advisoryItem['title'] ?? 'Unknown'),
                    (string) ($advisoryItem['affectedVersions'] ?? '*'),
                    $advisoryItem['cve'] ?? null,
                    $advisoryItem['severity'] ?? null,
                    $advisoryItem['link'] ?? null,
                );
            }
        }

        return $advisoriesByPackageName;
    }

    /**
     * @return string[] stable versions, ascending
     */
    public function fetchStableVersions(string $packageName): array
    {
        $response = $this->request(sprintf(self::VERSIONS_URL, $packageName));

        $versions = [];
        foreach ($response['packages'][$packageName] ?? [] as $versionItem) {
            $version = ltrim((string) $versionItem['version'], 'v');

            if (VersionParser::parseStability($version) !== 'stable') {
                continue;
            }

            $versions[] = $version;
        }

        usort($versions, fn (string $left, string $right): int => version_compare(
            $this->versionParser->normalize($left),
            $this->versionParser->normalize($right)
        ));

        return $versions;
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $url, ?string $postFields = null): array
    {
        $httpOptions = [
            'header' => 'User-Agent: ' . self::USER_AGENT . "\r\n",
            'timeout' => 20,
            'ignore_errors' => true,
        ];

        if ($postFields !== null) {
            $httpOptions['method'] = 'POST';
            $httpOptions['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $httpOptions['content'] = $postFields;
        }

        $streamContext = stream_context_create([
            'http' => $httpOptions,
        ]);

        $responseContents = @file_get_contents($url, false, $streamContext);
        if ($responseContents === false) {
            throw new SecHoleException(sprintf('Request to "%s" failed', $url));
        }

        $json = json_decode($responseContents, true);
        if (! is_array($json)) {
            throw new SecHoleException(sprintf('Response from "%s" is not valid JSON', $url));
        }

        return $json;
    }
}
