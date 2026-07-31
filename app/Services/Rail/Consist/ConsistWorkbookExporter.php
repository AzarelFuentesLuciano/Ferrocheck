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
    public function __construct(private string $formatReference)
    {
    }

    public function export(array $consist, string $directory): array
    {
        $period = ConsistOperationalPeriod::fromInput(
            (string) ($consist['fecha_inicio'] ?? ''),
            (string) ($consist['fecha_fin'] ?? ''),
        );
        if (!is_file($this->formatReference) || !is_readable($this->formatReference)) {
            throw new RuntimeException('La referencia visual del Consist no está disponible.');
        }
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar la exportación.');
        }

        $reference = IOFactory::load($this->formatReference);
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
        $this->copyFormat($reference->getSheetByName('Consist'), $consistSheet, 14, count($units) + 1, 'ConsistTable');
        $this->copyFormat($reference->getSheetByName('Summary'), $summarySheet, 7, count($summary) + 1, 'SummaryTable');

        $referenceVersion = $reference->getSheetByName('Version');
        for ($row = 1; $row <= 2; $row++) {
            for ($column = 1; $column <= 3; $column++) {
                $value = $referenceVersion?->getCell([$column, $row])->getValue();
                $versionSheet->setCellValueExplicit([$column, $row], $value ?? '', DataType::TYPE_STRING);
                if ($referenceVersion !== null) {
                    $coordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $row;
                    $versionSheet->duplicateStyle($referenceVersion->getStyle($coordinate), $coordinate);
                }
            }
        }
        $versionSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $book->setActiveSheetIndex(0);

        $filename = $period->filename();
        $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . bin2hex(random_bytes(16)) . '.xlsx';
        IOFactory::createWriter($book, 'Xlsx')->save($path);
        $reference->disconnectWorksheets();
        $book->disconnectWorksheets();
        return [
            'path' => $path,
            'filename' => $filename,
            'sha256' => hash_file('sha256', $path),
            'total_units' => count($units),
            'total_summary_rows' => count($summary),
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

    private function copyFormat(
        ?Worksheet $source,
        Worksheet $target,
        int $columns,
        int $lastRow,
        string $tableName,
    ): void
    {
        if ($source === null) {
            throw new RuntimeException('La referencia visual no contiene las hojas oficiales.');
        }
        for ($column = 1; $column <= $columns; $column++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
            $target->getColumnDimension($letter)->setWidth($source->getColumnDimension($letter)->getWidth());
        }
        $headerHeight = $source->getRowDimension(1)->getRowHeight();
        if ($headerHeight < 0) {
            $headerHeight = $source->getDefaultRowDimension()->getRowHeight();
        }
        $fallbackHeaderHeight = $tableName === 'SummaryTable' ? 15.0 : 12.75;
        $target->getRowDimension(1)->setRowHeight($headerHeight > 0 ? $headerHeight : $fallbackHeaderHeight);
        if ($lastRow >= 1) {
            $range = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columns) . $lastRow;
            $target->getStyle($range)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $target->getStyle($range)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_GENERAL);
            $referenceFont = $source->getStyle('A2')->getFont();
            $target->getStyle($range)->getFont()
                ->setName($referenceFont->getName())
                ->setSize($referenceFont->getSize());
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
        $freeze = $source->getFreezePane();
        if ($freeze !== null && $freeze !== '') {
            $target->freezePane($freeze);
        }
    }
}
