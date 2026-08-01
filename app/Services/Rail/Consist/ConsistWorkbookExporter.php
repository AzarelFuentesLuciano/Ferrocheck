<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

final class ConsistWorkbookExporter
{
    private const CONSIST_WIDTHS = [
        29.42578125, 25.140625, 14.28515625, 9.42578125, 10.85546875,
        12.85546875, 12.42578125, 27.0, 27.42578125, 22.28515625,
        33.42578125, 11.7109375, 15.0, 15.0,
    ];
    private const SUMMARY_WIDTHS = [
        33.140625, 14.28515625, 15.0, 30.7109375, 25.85546875, 16.140625, 9.42578125,
    ];
    private const SUMMARY_SIDE_WIDTHS = [
        'I' => 18.0,
        'J' => 28.7109375,
    ];

    public function __construct(?string $formatReference = null)
    {
    }

    public function export(array $consist, string $directory): array
    {
        $period = ConsistOperationalPeriod::fromInput(
            (string) ($consist['fecha_inicio'] ?? ''),
            (string) ($consist['fecha_fin'] ?? ''),
        );
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar la exportación.');
        }

        $book = new Spreadsheet();
        $consistSheet = $book->getActiveSheet();
        $consistSheet->setTitle('Consist');
        $summarySheet = $book->createSheet();
        $summarySheet->setTitle('Summary');
        $versionSheet = $book->createSheet();
        $versionSheet->setTitle('Version');

        $units = array_values((array) ($consist['units'] ?? []));
        $finalRows = array_map(fn (array $unit): array => $this->finalRow($unit), $units);
        $this->writeValues(
            $consistSheet,
            ConsistDocumentBuilder::HEADERS,
            array_map('array_values', $finalRows),
        );
        [$summary, $summaryWarnings] = $this->summary($finalRows);
        $this->writeValues($summarySheet, self::summaryHeaders(), $summary);
        $this->copyFormat($consistSheet, self::CONSIST_WIDTHS, count($units) + 1, 'ConsistTable');
        $this->copyFormat($summarySheet, self::SUMMARY_WIDTHS, count($summary) + 1, 'SummaryTable');
        $this->writeStaticSummaries($summarySheet, $summary);

        foreach ([
            ['Versión', 'Detalles', 'Fecha'],
            ['1.0M', 'Se configura el orden de las tablas al realizarse la consulta de datos.', '43430'],
        ] as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $versionSheet->setCellValueExplicit(
                    [$columnIndex + 1, $rowIndex + 1],
                    $value,
                    DataType::TYPE_STRING,
                );
            }
        }
        $versionSheet->getColumnDimension('A')->setWidth(9.140625);
        $versionSheet->getColumnDimension('B')->setWidth(68.0);
        $versionSheet->getColumnDimension('C')->setWidth(11.85546875);
        $versionSheet->getStyle('A1:C2')->getFont()->setName('Verdana')->setSize(10);
        $versionSheet->getStyle('A1:C1')->getFont()->setBold(true);
        $versionSheet->getStyle('A1:C1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $versionSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $book->setActiveSheetIndex(0);

        $filename = $period->filename();
        $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . bin2hex(random_bytes(16)) . '.xlsx';
        IOFactory::createWriter($book, 'Xlsx')->save($path);
        $book->disconnectWorksheets();
        return [
            'path' => $path,
            'filename' => $filename,
            'sha256' => hash_file('sha256', $path),
            'total_units' => count($units),
            'total_summary_rows' => count($summary),
            'operational_summary' => (array) ($consist['operational_summary'] ?? []),
            'summary_warnings' => $summaryWarnings,
        ];
    }

    public static function summaryHeaders(): array
    {
        return ['fdTransportationName1', 'Carrier', 'fdTrack', 'fdDestinationLocation', 'Cruce', 'Shippers', 'UT'];
    }

    private function finalRow(array $unit): array
    {
        $source = (array) ($unit['final_data_json'] ?? $unit['final_columns'] ?? []);
        if (array_is_list($source)) {
            $source = array_combine(
                ConsistDocumentBuilder::HEADERS,
                array_pad(array_slice($source, 0, count(ConsistDocumentBuilder::HEADERS)), count(ConsistDocumentBuilder::HEADERS), ''),
            );
        }

        $row = [];
        foreach (ConsistDocumentBuilder::HEADERS as $header) {
            $row[$header] = $source[$header] ?? '';
        }
        return $row;
    }

    private function summary(array $finalRows): array
    {
        $platforms = [];
        $warnings = [];
        foreach ($finalRows as $row) {
            $platform = trim((string) ($row['fdTransportationName1'] ?? ''));
            if ($platform === '') {
                throw new RuntimeException('El Consist contiene una unidad sin plataforma y no puede exportarse.');
            }
            if (!isset($platforms[$platform])) {
                $platforms[$platform] = [
                    $platform,
                    (string) ($row['Carrier'] ?? ''),
                    (string) ($row['fdTrack'] ?? ''),
                    (string) ($row['fdDestinationLocation'] ?? ''),
                    (string) ($row['Cruce'] ?? ''),
                    (string) ($row['Shipper'] ?? ''),
                    0,
                ];
            } else {
                $fields = ['Carrier', 'fdTrack', 'fdDestinationLocation', 'Cruce', 'Shipper'];
                foreach ($fields as $index => $field) {
                    $firstValue = (string) $platforms[$platform][$index + 1];
                    $currentValue = (string) ($row[$field] ?? '');
                    if ($currentValue !== $firstValue) {
                        $warnings[$platform][$field] ??= [
                            'first_value' => $firstValue,
                            'other_values' => [],
                        ];
                        if (!in_array($currentValue, $warnings[$platform][$field]['other_values'], true)) {
                            $warnings[$platform][$field]['other_values'][] = $currentValue;
                        }
                    }
                }
            }
            $platforms[$platform][6]++;
        }
        $structuredWarnings = [];
        foreach ($warnings as $platform => $fields) {
            $structuredWarnings[] = [
                'type' => 'summary_platform_conflicting_values',
                'platform' => $platform,
                'selected_rule' => 'first_consist_row',
                'fields' => $fields,
            ];
        }
        return [array_values($platforms), $structuredWarnings];
    }

    private function writeValues(Worksheet $sheet, array $headers, array $rows): void
    {
        foreach ($headers as $index => $header) {
            $sheet->setCellValueExplicit([$index + 1, 1], $header, DataType::TYPE_STRING);
        }
        foreach ($rows as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $header = (string) ($headers[$columnIndex] ?? '');
                $type = in_array($header, ['fdwholevin', 'Route Code', 'CNACS-Pedimento'], true)
                    ? DataType::TYPE_STRING
                    : (is_numeric($value) && !preg_match('/^0\d+$/', (string) $value)
                    ? DataType::TYPE_NUMERIC
                    : DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([$columnIndex + 1, $rowIndex + 2], $value, $type);
            }
        }
    }

    private function writeStaticSummaries(Worksheet $sheet, array $platformRows): void
    {
        $destinationCounts = $this->counts($platformRows, 3, true, false);
        $crossingCounts = $this->counts($platformRows, 4, false, true);

        $destinationEnd = $this->writeStaticSummary(
            $sheet,
            1,
            'Row Labels',
            'Cuenta de fdTransportationName1',
            $destinationCounts,
            count($platformRows),
        );
        $this->writeStaticSummary(
            $sheet,
            $destinationEnd + 3,
            'Row Labels',
            'Cantidad de plataformas',
            $crossingCounts,
            count($platformRows),
        );
        foreach (self::SUMMARY_SIDE_WIDTHS as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function counts(array $rows, int $column, bool $sortAscending, bool $includeBlank): array
    {
        $counts = $includeBlank ? ['(en blanco)' => 0] : [];
        foreach ($rows as $row) {
            $label = trim((string) ($row[$column] ?? ''));
            $label = $label === '' ? '(en blanco)' : $label;
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }
        if ($sortAscending) {
            uksort($counts, static fn (string $left, string $right): int => strnatcasecmp($left, $right));
        }
        return $counts;
    }

    private function writeStaticSummary(
        Worksheet $sheet,
        int $startRow,
        string $labelHeader,
        string $countHeader,
        array $counts,
        int $total,
    ): int {
        $sheet->setCellValueExplicit([9, $startRow], $labelHeader, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit([10, $startRow], $countHeader, DataType::TYPE_STRING);
        $sheet->getStyle("I{$startRow}:J{$startRow}")->getFont()->setName('Calibri')->setSize(10);
        $sheet->getStyle("I{$startRow}:J{$startRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $row = $startRow + 1;
        foreach ($counts as $label => $count) {
            $sheet->setCellValueExplicit([9, $row], $label, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([10, $row], $count, DataType::TYPE_NUMERIC);
            $row++;
        }
        $sheet->setCellValueExplicit([9, $row], 'Total general', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit([10, $row], $total, DataType::TYPE_NUMERIC);
        $sheet->getStyle("I" . ($startRow + 1) . ":J{$row}")->getFont()->setName('Calibri')->setSize(10);
        $sheet->getStyle("I" . ($startRow + 1) . ":I{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("J" . ($startRow + 1) . ":J{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        return $row;
    }

    private function copyFormat(
        Worksheet $target,
        array $widths,
        int $lastRow,
        string $tableName,
    ): void
    {
        $columns = count($widths);
        for ($column = 1; $column <= $columns; $column++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
            $target->getColumnDimension($letter)->setWidth((float) $widths[$column - 1]);
        }
        $target->getRowDimension(1)->setRowHeight($tableName === 'SummaryTable' ? 15.0 : 12.75);
        if ($lastRow >= 1) {
            $range = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns) . $lastRow;
            $target->getStyle($range)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $target->getStyle($range)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_GENERAL);
            $target->getStyle($range)->getFont()
                ->setName('Verdana')
                ->setSize(10);
            $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns) . '1';
            $target->getStyle($headerRange)->getFont()->setBold(true);
            $target->getStyle($headerRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF000000');
            $table = new Table($range, $tableName);
            $style = new TableStyle(TableStyle::TABLE_STYLE_MEDIUM2);
            $style->setShowRowStripes(true);
            $table->setStyle($style);
            $target->addTable($table);
        }
    }
}
