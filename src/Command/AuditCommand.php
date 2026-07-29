<?php

declare(strict_types=1);

namespace SecHole\Command;

use SecHole\ComposerLockParser;
use SecHole\Console\ConsolePrinter;
use SecHole\ValueObject\Advisory;
use SecHole\ValueObject\MinorBranchReport;
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
        $this->consolePrinter->title(
            sprintf('Checking %d %s', $packageCount, $packageCount === 1 ? 'package' : 'packages')
        );

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

        foreach ($vulnerablePackageReports as $packageReport) {
            $this->renderPackageReport($packageReport, $isDetailed);
        }

        if (! $isDetailed) {
            $this->consolePrinter->note('Run with <comment>--details</> to see advisory titles, CVEs and links');
        }

        $this->consolePrinter->error(sprintf('%d vulnerable packages found', count($vulnerablePackageReports)));

        return self::ERROR;
    }

    private function renderPackageReport(PackageReport $packageReport, bool $isDetailed): void
    {
        $this->consolePrinter->section(sprintf(
            '%s %s - %d known CVEs',
            $packageReport->getPackageName(),
            $packageReport->getInstalledVersion(),
            $packageReport->getAdvisoryCount()
        ));
        $this->consolePrinter->writeln();

        if ($isDetailed) {
            $this->renderAdvisories($packageReport);
        }

        $this->renderUpgradeTable($packageReport);
    }

    private function renderUpgradeTable(PackageReport $packageReport): void
    {
        $minorBranchReports = $packageReport->getMinorBranchReports();

        if ($minorBranchReports === []) {
            $this->consolePrinter->writeln('No newer release published.');
            $this->consolePrinter->writeln();

            return;
        }

        $rows = [];
        foreach ($minorBranchReports as $minorBranchReport) {
            $rows[] = [
                $this->createVersionCell($minorBranchReport),
                $this->describeAdvisoryCount($minorBranchReport),
                $minorBranchReport->getReleasedAt(),
            ];
        }

        // versions and counts read better flush right
        $this->consolePrinter->table(['Version', 'Known CVEs', 'Released'], $rows, [0, 1, 2]);
        $this->consolePrinter->writeln();
    }

    /**
     * The branch stands out, the patch part is only noise - 4.4<gray>.51</>
     */
    private function createVersionCell(MinorBranchReport $minorBranchReport): string
    {
        $minorBranch = $minorBranchReport->getMinorBranch();
        $patchPart = substr($minorBranchReport->getLatestVersion(), strlen($minorBranch));

        if ($patchPart === '' || $patchPart === false) {
            return $minorBranch;
        }

        return $minorBranch . '<gray>' . $patchPart . '</>';
    }

    private function describeAdvisoryCount(MinorBranchReport $minorBranchReport): string
    {
        if ($minorBranchReport->isClean()) {
            return 'none';
        }

        return (string) $minorBranchReport->getAdvisoryCount();
    }

    private function renderAdvisories(PackageReport $packageReport): void
    {
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
