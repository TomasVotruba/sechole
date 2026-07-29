<?php

declare(strict_types=1);

namespace SecHole;

use Composer\Semver\VersionParser;
use SecHole\Exception\SecHoleException;
use SecHole\ValueObject\Advisory;

final class PackagistClient
{
    private const ADVISORIES_URL = 'https://packagist.org/api/security-advisories/';

    private const VERSIONS_URL = 'https://repo.packagist.org/p2/%s.json';

    private const USER_AGENT = 'sechole/1.0 (+https://packagist.org)';

    /**
     * @var VersionParser
     */
    private $versionParser;

    public function __construct(VersionParser $versionParser)
    {
        $this->versionParser = $versionParser;
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

        $postFields = http_build_query(['packages' => array_values($packageNames)]);

        $response = $this->request(self::ADVISORIES_URL, $postFields);
        $advisoryItemsByPackageName = isset($response['advisories']) ? $response['advisories'] : [];

        $advisoriesByPackageName = [];
        foreach ($advisoryItemsByPackageName as $packageName => $advisoryItems) {
            foreach ($advisoryItems as $advisoryItem) {
                $advisoriesByPackageName[$packageName][] = new Advisory(
                    isset($advisoryItem['title']) ? (string) $advisoryItem['title'] : 'Unknown',
                    isset($advisoryItem['affectedVersions']) ? (string) $advisoryItem['affectedVersions'] : '*',
                    isset($advisoryItem['cve']) ? (string) $advisoryItem['cve'] : null,
                    isset($advisoryItem['severity']) ? (string) $advisoryItem['severity'] : null,
                    isset($advisoryItem['link']) ? (string) $advisoryItem['link'] : null
                );
            }
        }

        return $advisoriesByPackageName;
    }

    /**
     * @return array<string, string> stable version => release date (Y-m-d), ascending by version
     */
    public function fetchStableVersions(string $packageName): array
    {
        $response = $this->request(sprintf(self::VERSIONS_URL, $packageName));

        $versionItems = isset($response['packages'][$packageName]) ? $response['packages'][$packageName] : [];

        $releaseDatesByVersion = [];
        foreach ($versionItems as $versionItem) {
            $version = ltrim((string) $versionItem['version'], 'v');

            if (VersionParser::parseStability($version) !== 'stable') {
                continue;
            }

            // "2020-10-27T15:34:22+00:00" -> "2020-10-27"
            $releaseDatesByVersion[$version] = isset($versionItem['time'])
                ? substr((string) $versionItem['time'], 0, 10)
                : '-';
        }

        $versionParser = $this->versionParser;
        uksort($releaseDatesByVersion, function (string $left, string $right) use ($versionParser): int {
            return version_compare($versionParser->normalize($left), $versionParser->normalize($right));
        });

        return $releaseDatesByVersion;
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

        $streamContext = stream_context_create(['http' => $httpOptions]);

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
