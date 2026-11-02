<?php

namespace App\Domain;

use Carbon\CarbonImmutable;

// "show me March", "show me 2026" or "show me March 2026" as a first and a last day.
// A month on its own means that month of the current year, because "March" alone is
// otherwise ambiguous.
final class PeriodFilter
{
    private function __construct(
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly ?int $year,
        public readonly ?int $month,
    ) {
    }

    /** No filter at all. */
    public static function none(): self
    {
        return new self(null, null, null, null);
    }

    /**
     * Builds the filter from what came in with the request (strings, empty or missing).
     *
     * @throws InvalidPeriod when the month or year is not a sensible number
     */
    public static function fromInput(mixed $year, mixed $month, CarbonImmutable $today): self
    {
        $year = self::number($year, 'year');
        $month = self::number($month, 'month');

        if ($month !== null && ($month < 1 || $month > 12)) {
            throw new InvalidPeriod('Please choose a month between January and December.');
        }

        if ($year !== null && ($year < 2000 || $year > 2100)) {
            throw new InvalidPeriod('Please choose a year between 2000 and 2100.');
        }

        if ($year === null && $month === null) {
            return self::none();
        }

        $year ??= $today->year;

        if ($month === null) {
            return new self("$year-01-01", "$year-12-31", $year, null);
        }

        $first = CarbonImmutable::create($year, $month, 1)->startOfDay();

        return new self($first->format('Y-m-d'), $first->endOfMonth()->format('Y-m-d'), $year, $month);
    }

    public function isActive(): bool
    {
        return $this->from !== null;
    }

    /** "March 2026", "2026" or null when nothing is filtered. */
    public function label(): ?string
    {
        if (! $this->isActive()) {
            return null;
        }

        if ($this->month === null) {
            return (string) $this->year;
        }

        return CarbonImmutable::create($this->year, $this->month, 1)->format('F Y');
    }

    private static function number(mixed $value, string $what): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_scalar($value) || ! ctype_digit(trim((string) $value))) {
            throw new InvalidPeriod(sprintf('That is not a valid %s.', $what));
        }

        return (int) $value;
    }
}
