<?php

declare(strict_types=1);

namespace SecHole\Console;

/**
 * A pocket-sized take on the Symfony Console output style - titles, sections,
 * blocks and tables - without pulling in symfony/console.
 *
 * Text can carry tags: <info>, <comment>, <error>, <fg=green> ... </>.
 * They turn into ANSI colors on a terminal and are stripped everywhere else.
 */
final class ConsolePrinter
{
    private const COLUMN_PADDING = 2;

    private const BLOCK_PADDING = 2;

    private const MIN_BLOCK_WIDTH = 40;

    /**
     * @var array<string, string>
     */
    private const ANSI_CODES = [
        'black' => '30',
        'red' => '31',
        'green' => '32',
        'yellow' => '33',
        'blue' => '34',
        'magenta' => '35',
        'cyan' => '36',
        'white' => '37',
        'bold' => '1',
    ];

    /**
     * @var array<string, string>
     */
    private const TAG_ALIASES = [
        'info' => 'green',
        'comment' => 'yellow',
        'error' => 'red',
        'question' => 'cyan',
    ];

    /**
     * @var bool|null
     */
    private $isDecorated;

    public function writeln(string $line = ''): void
    {
        echo $this->format($line) . PHP_EOL;
    }

    public function text(string $text): void
    {
        $this->writeln(' ' . $text);
    }

    public function title(string $text): void
    {
        $this->writeln('<comment>' . $text . '</>');
        $this->writeln('<comment>' . str_repeat('=', $this->getVisibleLength($text)) . '</>');
        $this->writeln();
    }

    public function section(string $text): void
    {
        $this->writeln('<info>' . $text . '</>');
        $this->writeln('<info>' . str_repeat('-', $this->getVisibleLength($text)) . '</>');
    }

    /**
     * @param string[] $items
     */
    public function listing(array $items): void
    {
        foreach ($items as $item) {
            $this->writeln(' * ' . $item);
        }

        $this->writeln();
    }

    public function success(string $text): void
    {
        $this->block('OK', $text, 'green', 'black');
    }

    public function warning(string $text): void
    {
        $this->block('WARNING', $text, 'yellow', 'black');
    }

    public function error(string $text): void
    {
        $this->block('ERROR', $text, 'red', 'white');
    }

    public function note(string $text): void
    {
        $this->writeln(' <comment>!</> ' . $text);
        $this->writeln();
    }

    /**
     * @param string[] $headers
     * @param string[][] $rows
     * @param int[] $rightAlignedColumns positions of columns to align right
     */
    public function table(array $headers, array $rows, array $rightAlignedColumns = []): void
    {
        $columnWidths = $this->resolveColumnWidths($headers, $rows);
        $borderLine = $this->createBorderLine($columnWidths);

        $coloredHeaders = [];
        foreach ($headers as $header) {
            $coloredHeaders[] = '<info>' . $header . '</>';
        }

        $this->writeln($borderLine);
        $this->writeln($this->formatRow($coloredHeaders, $columnWidths, $rightAlignedColumns));
        $this->writeln($borderLine);

        foreach ($rows as $row) {
            $this->writeln($this->formatRow($row, $columnWidths, $rightAlignedColumns));
        }

        $this->writeln($borderLine);
    }

    /**
     * A full-width colored block with a blank padded line above and below, Symfony style.
     */
    private function block(string $label, string $text, string $backgroundColor, string $foregroundColor): void
    {
        $content = sprintf('[%s] %s', $label, $text);
        $width = max($this->getVisibleLength($content) + 2 * self::BLOCK_PADDING, self::MIN_BLOCK_WIDTH);

        $lines = [
            str_repeat(' ', $width),
            str_pad(str_repeat(' ', self::BLOCK_PADDING) . $content, $width),
            str_repeat(' ', $width),
        ];

        // foreground 3x has a matching background 4x
        $ansiCode = self::ANSI_CODES[$foregroundColor] . ';' . ((int) self::ANSI_CODES[$backgroundColor] + 10);

        $this->writeln();
        foreach ($lines as $line) {
            $this->writeln($this->decorate($line, $ansiCode));
        }

        $this->writeln();
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
                $width = $this->getVisibleLength($value);
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
     * @param int[] $rightAlignedColumns
     */
    private function formatRow(array $row, array $columnWidths, array $rightAlignedColumns = []): string
    {
        $parts = [];
        foreach (array_values($row) as $columnPosition => $value) {
            // pad by visible length, so tags do not shift the columns
            $padding = str_repeat(' ', max(0, $columnWidths[$columnPosition] - $this->getVisibleLength($value)));

            $parts[] = in_array($columnPosition, $rightAlignedColumns, true)
                ? $padding . $value
                : $value . $padding;
        }

        return '  ' . implode('   ', $parts);
    }

    private function format(string $text): string
    {
        if (! $this->isDecorated()) {
            return $this->stripTags($text);
        }

        $text = preg_replace_callback(
            '#<(?:fg=)?([a-z]+)>#',
            function (array $match): string {
                $color = isset(self::TAG_ALIASES[$match[1]]) ? self::TAG_ALIASES[$match[1]] : $match[1];

                return isset(self::ANSI_CODES[$color]) ? "\033[" . self::ANSI_CODES[$color] . 'm' : $match[0];
            },
            $text
        );

        return preg_replace('#</[a-z]*>#', "\033[0m", (string) $text);
    }

    private function stripTags(string $text): string
    {
        return (string) preg_replace('#</?(?:fg=)?[a-z]*>#', '', $text);
    }

    private function getVisibleLength(string $text): int
    {
        return strlen($this->stripTags($text));
    }

    private function decorate(string $text, string $ansiCode): string
    {
        if (! $this->isDecorated()) {
            return $text;
        }

        return "\033[" . $ansiCode . 'm' . $text . "\033[0m";
    }

    private function isDecorated(): bool
    {
        if ($this->isDecorated === null) {
            $this->isDecorated = getenv('NO_COLOR') === false
                && function_exists('posix_isatty')
                && @posix_isatty(STDOUT);
        }

        return $this->isDecorated;
    }
}
