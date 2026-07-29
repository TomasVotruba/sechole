<?php

declare(strict_types=1);

namespace SecHole\Console;

final class ConsolePrinter
{
    private const COLUMN_PADDING = 2;

    public function writeln(string $line = ''): void
    {
        echo $line . PHP_EOL;
    }

    public function title(string $text): void
    {
        $this->writeln($text);
        $this->writeln(str_repeat('=', strlen($text)));
        $this->writeln();
    }

    public function section(string $text): void
    {
        $this->writeln($text);
        $this->writeln(str_repeat('-', strlen($text)));
    }

    /**
     * @param string[] $items
     */
    public function listing(array $items): void
    {
        foreach ($items as $item) {
            $this->writeln('* ' . $item);
        }

        $this->writeln();
    }

    public function success(string $text): void
    {
        $this->writeln($this->colorize(' [OK] ' . $text . ' ', '42;30'));
    }

    public function warning(string $text): void
    {
        $this->writeln($this->colorize(' [WARNING] ' . $text . ' ', '43;30'));
    }

    public function error(string $text): void
    {
        $this->writeln($this->colorize(' [ERROR] ' . $text . ' ', '41;37'));
    }

    /**
     * @param string[] $headers
     * @param string[][] $rows
     */
    public function table(array $headers, array $rows): void
    {
        $columnWidths = $this->resolveColumnWidths($headers, $rows);
        $borderLine = $this->createBorderLine($columnWidths);

        $this->writeln($borderLine);
        $this->writeln($this->formatRow($headers, $columnWidths));
        $this->writeln($borderLine);

        foreach ($rows as $row) {
            $this->writeln($this->formatRow($row, $columnWidths));
        }

        $this->writeln($borderLine);
    }

    /**
     * @param string[] $headers
     * @param string[][] $rows
     * @return int[]
     */
    private function resolveColumnWidths(array $headers, array $rows): array
    {
        $columnWidths = [];

        foreach (array_merge([$headers], $rows) as $row) {
            foreach (array_values($row) as $columnPosition => $value) {
                $width = strlen($value);
                if (! isset($columnWidths[$columnPosition]) || $columnWidths[$columnPosition] < $width) {
                    $columnWidths[$columnPosition] = $width;
                }
            }
        }

        return $columnWidths;
    }

    /**
     * @param int[] $columnWidths
     */
    private function createBorderLine(array $columnWidths): string
    {
        $parts = [];
        foreach ($columnWidths as $columnWidth) {
            $parts[] = str_repeat('-', $columnWidth + self::COLUMN_PADDING);
        }

        return ' ' . implode(' ', $parts);
    }

    /**
     * @param string[] $row
     * @param int[] $columnWidths
     */
    private function formatRow(array $row, array $columnWidths): string
    {
        $parts = [];
        foreach (array_values($row) as $columnPosition => $value) {
            $parts[] = str_pad($value, $columnWidths[$columnPosition]);
        }

        return '  ' . implode('   ', $parts);
    }

    private function colorize(string $text, string $ansiCode): string
    {
        if (! $this->isColorSupported()) {
            return $text;
        }

        return "\033[" . $ansiCode . 'm' . $text . "\033[0m";
    }

    private function isColorSupported(): bool
    {
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        return function_exists('posix_isatty') && @posix_isatty(STDOUT);
    }
}
