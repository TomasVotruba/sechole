<?php

declare(strict_types=1);

namespace SecHole\Command;

use SecHole\ComposerLockParser;
use SecHole\Console\ConsolePrinter;
use SecHole\ValueObject\Advisory;
use SecHole\ValueObject\PackageReport;
use SecHole\VulnerabilityAnalyser;

final class AuditCommand
{
    public const SUCCESS = 0;

    public const ERROR = 1;

    /**
     * @var ComposerLockParser
     */
    private $composerLockParser;

    /**
     * @var VulnerabilityAnalyser
     */
    private $vulnerabilityAnalyser;

    /**
     * @var ConsolePrinter
     */
    private $consolePrinter;

    public function __construct(
        ComposerLockParser $composerLockParser,
        VulnerabilityAnalyser $vulnerabilityAnalyser,
        ConsolePrinter $consolePrinter
    ) {
        $this->composerLockParser = $composerLockParser;
        $this->vulnerabilityAnalyser = $vulnerabilityAnalyser;
        $this->consolePrinter = $consolePrinter;
    }

    public function run(string $composerLockFilePath, bool $isDetailed = false): int
    {
        $installedPackages = $this->composerLockParser->parse($composerLockFilePath);

        if ($installedPackages === []) {
            $this->consolePrinter->warning('No symfony, twig, doctrine or illuminate package found');

            return self::SUCCESS;
        }

        $packageCount = count($installedPackages);
        $this->consolePrinter->title(sprintf('Checking %d %s', $packageCount, $packageCount === 1 ? 'package' : 'packages'));

        $packageReports = $this->vulnerabilityAnalyser->analyse($installedPackages);

        $vulnerablePackageReports = array_values(array_filter(
            $packageReports,
            function (PackageReport $packageReport): bool {
                return ! $packageReport->isClean();
            }
        ));

        if ($vulnerablePackageReports === []) {
            $this->consolePrinter->success('No known vulnerability found');

            return self::SUCCESS;
        }

        $this->renderOverviewTable($vulnerablePackageReports);

        if ($isDetailed) {
            $this->renderDetails($vulnerablePackageReports);
        } else {
            $this->consolePrinter->writeln();
            $this->consolePrinter->writeln('Run with --details to see advisory titles, CVEs and links');
        }

        $this->consolePrinter->writeln();
        $this->consolePrinter->error(sprintf('%d vulnerable packages found', count($vulnerablePackageReports)));

        return self::ERROR;
    }

    /**
     * @param PackageReport[] $packageReports
     */
    private function renderOverviewTable(array $packageReports): void
    {
        $rows = [];
        foreach ($packageReports as $packageReport) {
            $rows[] = [
                $packageReport->getPackageName(),
                $packageReport->getInstalledVersion(),
                (string) $packageReport->getAdvisoryCount(),
                $this->createRecommendation($packageReport),
            ];
        }

        $this->consolePrinter->table(['Package', 'Current', 'Known CVEs', 'Recommended upgrade'], $rows);
    }

    private function createRecommendation(PackageReport $packageReport): string
    {
        $recommendedVersion = $packageReport->getRecommendedVersion();
        if ($recommendedVersion === null) {
            return 'none available';
        }

        if ($packageReport->getRecommendedVersionAdvisoryCount() === 0) {
            return $recommendedVersion . ' (clean)';
        }

        return sprintf(
            '%s (%d left)',
            $recommendedVersion,
            $packageReport->getRecommendedVersionAdvisoryCount()
        );
    }

    /**
     * @param PackageReport[] $packageReports
     */
    private function renderDetails(array $packageReports): void
    {
        foreach ($packageReports as $packageReport) {
            $this->consolePrinter->writeln();
            $this->consolePrinter->section(
                $packageReport->getPackageName() . ' ' . $packageReport->getInstalledVersion()
            );

            $lines = array_map(function (Advisory $advisory): string {
                return sprintf(
                    '[%s] %s (%s) %s',
                    $advisory->getSeverity(),
                    $advisory->getTitle(),
                    $advisory->getCve(),
                    $advisory->getLink()
                );
            }, $packageReport->getAdvisories());

            $this->consolePrinter->listing($lines);
        }
    }
}
