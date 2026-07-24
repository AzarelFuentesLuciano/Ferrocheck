<?php

declare(strict_types=1);

namespace App\Services\ControlEscaneres\Qr;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use PDO;
use OutOfBoundsException;

final class ScannerQrCodeService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Genera el código QR como SVG.
     *
     * No necesita GD ni Imagick.
     *
     * @return array{
     *     bytes:string,
     *     mime:string,
     *     extension:string,
     *     code:string,
     *     tag:string,
     *     content:string
     * }
     */
    public function svg(int $id, int $size = 300): array
    {
        $scanner = $this->findScanner($id);
        $content = $this->resolveContent((string) $scanner['codigo_qr']);
        $qrCode = $this->buildQrCode($content, $size);

        $result = (new SvgWriter())->write($qrCode);

        return [
            'bytes' => $result->getString(),
            'mime' => $result->getMimeType(),
            'extension' => 'svg',
            'code' => (string) $scanner['codigo'],
            'tag' => (string) $scanner['tag_original'],
            'content' => $content,
        ];
    }

    /**
     * @return array{codigo:string,codigo_qr:string,tag_original:string}
     */
    private function findScanner(int $id): array
    {
        $statement = $this->pdo->prepare(
            'SELECT codigo, codigo_qr, tag_original
             FROM scanners
             WHERE id = :id'
        );

        $statement->execute(['id' => $id]);
        $scanner = $statement->fetch(PDO::FETCH_ASSOC);

        if (!is_array($scanner)) {
            throw new OutOfBoundsException('Escáner no encontrado.');
        }

        return [
            'codigo' => (string) ($scanner['codigo'] ?? ''),
            'codigo_qr' => (string) ($scanner['codigo_qr'] ?? ''),
            'tag_original' => (string) ($scanner['tag_original'] ?? ''),
        ];
    }

    private function resolveContent(string $storedContent): string
    {
        $storedContent = trim($storedContent);

        if (
            str_starts_with($storedContent, '/')
            || preg_match('#^https?://#i', $storedContent) === 1
        ) {
            return $storedContent;
        }

        return ScannerQrContentNormalizer::canonicalUrl($storedContent);
    }

    private function buildQrCode(string $content, int $size): QrCode
    {
        return new QrCode(
            data: $content,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: max(160, min(1000, $size)),
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );
    }
}
