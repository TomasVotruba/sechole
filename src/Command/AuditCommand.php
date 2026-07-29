<?php

declare(strict_types=1);

namespace SecHole\Command;

use Entropy\Console\ConsoleTable\ConsoleTable;
use Entropy\Console\Contract\CommandInterface;
use Entropy\Console\Enum\ExitCode;
use Entropy\Console\Output\OutputPrinter;
use SecHole\ComposerLockParser;
use SecHole\ValueObject\Advisory;
use SecHole\ValueObject\PackageReport;
use SecHole\VulnerabilityAnalyser;

final readonly class AuditCommand implements CommandInterface
{
    public function __construct(
        private ComposerLockParser $composerLockParser,
        private VulnerabilityAnalyser $vulnerabilityAnalyser,
        private OutputPrinter $outputPrinter,
        private ConsoleTable $consoleTable
    ) {
    }

    public function getName(): string
    {
        return 'audit';
    }

    public function getDescription(): string
    {
        return 'List known vulnerabilities of symfony, twig, doctrine and illuminate packages in composer.lock';
    }

    /**
     * @param string $composerLock Path to the composer.lock file.
     * @param bool $details Print advisory titles, CVEs and links.
     */
    public function run(string $composerLock = 'composer.lock', bool $details = false): int
    {
        $installedPackages = $this->composerLockParser->parse($composerLock);

        if ($installedPackages === []) {
            $this->outputPrinter->warning('No symfony, twig, doctrine or illuminate package found');

            return ExitCode::SUCCESS;
        }

        $this->outputPrinter->title(sprintf('Checking %d packages', count($installedPackages)));

        $packageReports = $this->vulnerabilityAnalyser->analyse($installedPackages);
        $vulnerablePackageReports = array_values(array_filter(
            $packageReports,
            static fn (PackageReport $packageReport): bool => ! $packageReport->isClean()
        ));

        if ($vulnerablePackageReports === []) {
            $this->outputPrinter->success('No known vulnerability found');

            return ExitCode::SUCCESS;
        }

        $this->renderOverviewTable($vulnerablePackageReports);

        if ($details) {
            $this->renderDetails($vulnerablePackageReports);
        } else {
            $this->outputPrinter->newline();
            $this->outputPrinter->writeln('Run with --details to see advisory titles, CVEs and links');
        }

        $this->outputPrinter->newline();
        $this->outputPrinter->error(sprintf('%d vulnerable packages found', count($vulnerablePackageReports)));

        return ExitCode::ERROR;
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

        $this->consoleTable->render(['Package', 'Current', 'Known CVEs', 'Recommended upgrade'], $rows);
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
            $this->outputPrinter->section(
                $packageReport->getPackageName() . ' ' . $packageReport->getInstalledVersion()
            );

            $lines = array_map(
                static fn (Advisory $advisory): string => sprintf(
                    '[%s] %s (%s) %s',
                    $advisory->getSeverity(),
                    $advisory->getTitle(),
                    $advisory->getCve(),
                    $advisory->getLink()
                ),
                $packageReport->getAdvisories()
            );

            $this->outputPrinter->listing($lines);
        }
    }
}
