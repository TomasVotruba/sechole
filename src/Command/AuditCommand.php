<?php

declare(strict_types=1);

namespace SecHole\Command;

use SecHole\ComposerLockParser;
use SecHole\Console\ConsolePrinter;
use SecHole\ValueObject\MinorBranchReport;
use SecHole\ValueObject\PackageReport;
use SecHole\VulnerabilityAnalyser;

final class AuditCommand
{
    public const SUCCESS = 0;

    public const ERROR = 1;

    /**
     * @var int
     */
    private const ALARMING_ADVISORY_COUNT = 20;

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

    public function run(string $composerLockFilePath): int
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
            $this->renderPackageReport($packageReport);
        }

        $this->consolePrinter->error(sprintf('%d vulnerable packages found', count($vulnerablePackageReports)));

        return self::ERROR;
    }

    private function renderPackageReport(PackageReport $packageReport): void
    {
        $this->consolePrinter->section(sprintf(
            '%s %s - %d known CVEs',
            $packageReport->getPackageName(),
            $packageReport->getInstalledVersion(),
            $packageReport->getAdvisoryCount()
        ));
        $this->consolePrinter->writeln();

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
                '<gray>' . $minorBranchReport->getReleasedAt() . '</>',
            ];
        }

        // versions and counts read better flush right
        $this->consolePrinter->table(['Version', 'Known CVEs', 'Released'], $rows, [0, 1, 2]);
        $this->consolePrinter->writeln();
    }

    /**
     * The minor branch carries the signal, the patch part is noise - 4.4<dim>.51</>
     */
    private function createVersionCell(MinorBranchReport $minorBranchReport): string
    {
        $minorBranch = $minorBranchReport->getMinorBranch();
        $patchPart = substr($minorBranchReport->getLatestVersion(), strlen($minorBranch));

        if ($patchPart === '' || $patchPart === false) {
            return '<bold>' . $minorBranch . '</>';
        }

        return '<bold>' . $minorBranch . '</><dim>' . $patchPart . '</>';
    }

    private function describeAdvisoryCount(MinorBranchReport $minorBranchReport): string
    {
        if ($minorBranchReport->isClean()) {
            return '<gray>-</>';
        }

        $advisoryCount = $minorBranchReport->getAdvisoryCount();

        // a pile this big deserves to be noticed
        if ($advisoryCount > self::ALARMING_ADVISORY_COUNT) {
            return '<bold>' . $advisoryCount . '</>';
        }

        return (string) $advisoryCount;
    }
}
