<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;

final readonly class ConsistOperationalPeriod
{
    public function __construct(
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
    ) {
        if ($end < $start) {
            throw new DomainException('La fecha final no puede ser anterior a la fecha inicial.');
        }
    }

    public static function fromInput(string $start, string $end, ?DateTimeZone $timezone = null): self
    {
        if ($start === '' || $end === '') {
            throw new DomainException('Las fechas inicial y final son obligatorias.');
        }
        $timezone ??= new DateTimeZone(date_default_timezone_get());
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', $start, $timezone);
        $to = DateTimeImmutable::createFromFormat('!Y-m-d', $end, $timezone);
        if ($from === false || $to === false
            || $from->format('Y-m-d') !== $start || $to->format('Y-m-d') !== $end
        ) {
            throw new DomainException('El periodo operativo no tiene un formato válido.');
        }
        return new self($from, $to);
    }

    public function filename(): string
    {
        $start = $this->start;
        $end = $this->end;
        if ($start == $end) {
            $label = sprintf('%s de %s de %s', $start->format('j'), $this->month($start), $start->format('Y'));
        } elseif ($start->format('Y-m') === $end->format('Y-m')) {
            $label = sprintf(
                '%s al %s de %s de %s',
                $start->format('j'),
                $end->format('j'),
                $this->month($start),
                $start->format('Y'),
            );
        } elseif ($start->format('Y') === $end->format('Y')) {
            $label = sprintf(
                '%s de %s al %s de %s de %s',
                $start->format('j'),
                $this->month($start),
                $end->format('j'),
                $this->month($end),
                $start->format('Y'),
            );
        } else {
            $label = sprintf(
                '%s de %s de %s al %s de %s de %s',
                $start->format('j'),
                $this->month($start),
                $start->format('Y'),
                $end->format('j'),
                $this->month($end),
                $end->format('Y'),
            );
        }
        $filename = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/u', '-', 'Consist Rail del ' . $label . '.xlsx');
        return trim((string) $filename, " .\t\n\r\0\x0B");
    }

    private function month(DateTimeImmutable $date): string
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ][(int) $date->format('n')];
    }
}
