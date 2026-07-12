<?php

namespace App\Services;

use App\Repositories\InventarioRepository;

class VerificadorService
{
    private InventarioRepository $inventarioRepository;

    public function __construct(?InventarioRepository $inventarioRepository = null)
    {
        $this->inventarioRepository = $inventarioRepository ?? new InventarioRepository();
    }

    public function verificarEquipos(array $equipos): array
    {
        $equiposNormalizados = [];

        foreach ($equipos as $equipo) {
            $codigo = strtoupper(trim((string) $equipo));
            if ($codigo === '') {
                continue;
            }

            if (!in_array($codigo, $equiposNormalizados, true)) {
                $equiposNormalizados[] = $codigo;
            }
        }

        if (empty($equiposNormalizados)) {
            return [
                'inventario_ferromex' => $this->inventarioRepository->contarInventario(),
                'en_encantada' => 0,
                'otra_ubicacion' => 0,
                'no_encontrado' => 0,
                'resultados' => [],
            ];
        }

        $registros = $this->inventarioRepository->buscarEquipos($equiposNormalizados);
        $registrosPorEquipo = [];

        foreach ($registros as $registro) {
            $codigo = strtoupper((string) ($registro['equipo'] ?? ''));
            if ($codigo !== '' && !isset($registrosPorEquipo[$codigo])) {
                $registrosPorEquipo[$codigo] = $registro;
            }
        }

        $resultados = [];
        $enEncantada = 0;
        $otraUbicacion = 0;
        $noEncontrado = 0;

        foreach ($equiposNormalizados as $codigo) {
            $registro = $registrosPorEquipo[$codigo] ?? null;

            if ($registro === null) {
                $noEncontrado++;
                $resultados[] = [
                    'codigo' => $codigo,
                    'transportista' => '',
                    'ubicacion' => 'Sin registro',
                    'estado' => 'NO_ENCONTRADO',
                    'ultima_actualizacion' => '',
                    'evidencia' => '—',
                    'accion' => 'Ver',
                ];
                continue;
            }

            $ubicacion = $this->resolverUbicacion($registro);
            $estado = stripos($ubicacion, 'ENCANTADA') !== false ? 'EN_ENCANTADA' : 'OTRA_UBICACION';

            if ($estado === 'EN_ENCANTADA') {
                $enEncantada++;
            } else {
                $otraUbicacion++;
            }

            $resultados[] = [
                'codigo' => $codigo,
                'transportista' => (string) ($registro['ferrocarril_actual'] ?? $registro['ferrocarril'] ?? ''),
                'ubicacion' => $ubicacion,
                'estado' => $estado,
                'ultima_actualizacion' => (string) ($registro['fecha_importacion'] ?? ''),
                'evidencia' => 'Disponible',
                'accion' => 'Ver',
            ];
        }

        return [
            'inventario_ferromex' => $this->inventarioRepository->contarInventario(),
            'en_encantada' => $enEncantada,
            'otra_ubicacion' => $otraUbicacion,
            'no_encontrado' => $noEncontrado,
            'resultados' => $resultados,
        ];
    }

    private function resolverUbicacion(array $registro): string
    {
        $campos = [
            'estacion',
            'estacion_de_ultimo_movimiento',
            'estacion_de_destino',
            'estacion_de_origen',
        ];

        foreach ($campos as $campo) {
            $valor = trim((string) ($registro[$campo] ?? ''));
            if ($valor !== '') {
                return $valor;
            }
        }

        return 'Sin registro';
    }
}
