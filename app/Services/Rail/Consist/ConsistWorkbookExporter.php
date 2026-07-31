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
        $this->writeValues(
            $consistSheet,
            ConsistDocumentBuilder::HEADERS,
            array_map(static fn (array $unit): array => array_values((array) ($unit['final_data_json'] ?? $unit['final_columns'] ?? [])), $units),
        );
        $summary = $this->summary($units);
        $this->writeValues($summarySheet, self::summaryHeaders(), $summary);
        $operationalSummary = (array) ($consist['operational_summary'] ?? []);
        $this->copyFormat($consistSheet, self::CONSIST_WIDTHS, count($units) + 1, 'ConsistTable');
        $this->copyFormat($summarySheet, self::SUMMARY_WIDTHS, count($summary) + 1, 'SummaryTable');
        $this->writeOperationalSummary($summarySheet, $operationalSummary);

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
            'operational_summary' => $operationalSummary,
        ];
    }

    public static function summaryHeaders(): array
    {
        return ['fdTransportationName1', 'Carrier', 'fdTrack', 'fdDestinationLocation', 'Cruce', 'Shippers', 'UT'];
    }

    private function summary(array $units): array
    {
        $frequency = [];
        foreach ($units as $unit) {
            $vin = (string) ($unit['vin'] ?? '');
            $frequency[$vin] = ($frequency[$vin] ?? 0) + 1;
        }
        $platforms = [];
        foreach ($units as $unit) {
            if (($frequency[(string) ($unit['vin'] ?? '')] ?? 0) !== 1) {
                continue;
            }
            $row = (array) ($unit['final_data_json'] ?? $unit['final_columns'] ?? []);
            $platform = (string) ($row['fdTransportationName1'] ?? '');
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
            }
            $platforms[$platform][6]++;
        }
        return array_values($platforms);
    }

    private function writeValues(Worksheet $sheet, array $headers, array $rows): void
    {
        foreach ($headers as $index => $header) {
            $sheet->setCellValueExplicit([$index + 1, 1], $header, DataType::TYPE_STRING);
        }
        foreach ($rows as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $type = ($headers[$columnIndex] ?? '') === 'fdwholevin'
                    ? DataType::TYPE_STRING
                    : (is_numeric($value) && !preg_match('/^0\d+$/', (string) $value)
                    ? DataType::TYPE_NUMERIC
                    : DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([$columnIndex + 1, $rowIndex + 2], $value, $type);
            }
        }
    }

    private function writeOperationalSummary(Worksheet $sheet, array $summary): void
    {
        $metrics = [
            'Plataformas Pendientes de Confirmar' => (int) ($summary['pending_platforms'] ?? 0),
            'Plataformas Confirmadas' => (int) ($summary['confirmed_platforms'] ?? 0),
            'Total de Plataformas Cargadas' => (int) ($summary['total_loaded_platforms'] ?? 0),
        ];
        $row = 1;
        foreach ($metrics as $label => $value) {
            $sheet->setCellValueExplicit([9, $row], $label, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([10, $row], $value, DataType::TYPE_NUMERIC);
            $row++;
        }
        $sheet->getColumnDimension('I')->setWidth(38);
        $sheet->getColumnDimension('J')->setWidth(14);
        $sheet->getStyle('I1:I3')->getFont()->setBold(true);
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
