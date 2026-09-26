<?php

namespace App\Services;

/**
 * Calcula, mes a mes, los campos que pide el SAT en el pago provisional de ISR
 * (Actividad Empresarial y Profesional, art. 106 LISR) y en el definitivo de IVA.
 *
 * El ISR es acumulado dentro del año: ingresos y gastos de enero al mes, con la
 * tarifa del art. 96 multiplicada por el número de meses, menos los pagos
 * provisionales anteriores y las retenciones. El saldo a favor de IVA se arrastra
 * a los meses siguientes hasta agotarse.
 */
class DeclaracionSat
{
    /**
     * Tarifa mensual del art. 96 LISR (Anexo 8 RMF): [límite inferior, cuota fija, % excedente].
     * La de 2022 rigió de 2020 a 2022; la de 2023 se actualizó ese año.
     */
    private const TARIFAS = [
        2022 => [
            [0.01, 0.00, 1.92], [644.59, 12.38, 6.40], [5470.93, 321.26, 10.88], [9614.67, 772.10, 16.00],
            [11176.63, 1022.01, 17.92], [13381.48, 1417.12, 21.36], [26988.51, 4323.58, 23.52], [42537.59, 7980.73, 30.00],
            [81211.26, 19582.83, 32.00], [108281.68, 28245.36, 34.00], [324845.02, 101876.90, 35.00],
        ],
        2023 => [
            [0.01, 0.00, 1.92], [746.05, 14.32, 6.40], [6332.06, 371.83, 10.88], [11128.02, 893.63, 16.00],
            [12935.83, 1182.88, 17.92], [15487.72, 1640.18, 21.36], [31236.50, 5004.12, 23.52], [49233.01, 9236.89, 30.00],
            [93993.91, 22665.17, 32.00], [125325.21, 32691.18, 34.00], [375975.62, 117912.32, 35.00],
        ],
    ];

    /** Año de la tarifa que se aplica a un ejercicio. */
    public static function añoTarifa(int $año): int
    {
        return max(array_filter(array_keys(self::TARIFAS), fn ($a) => $a <= $año) ?: [2022]);
    }

    /** ISR de la tarifa acumulada a $meses meses sobre una base gravable. */
    public static function isrTarifa(float $base, int $año, int $meses): float
    {
        if ($base <= 0) {
            return 0.0;
        }
        foreach (array_reverse(self::TARIFAS[self::añoTarifa($año)]) as [$limite, $cuota, $tasa]) {
            if ($base >= $limite * $meses) {
                return round($cuota * $meses + ($base - $limite * $meses) * $tasa / 100, 2);
            }
        }

        return 0.0;
    }

    /**
     * Hojas de todos los meses, en orden, desde enero de $primerAño hasta $hastaAño-$hastaMes.
     *
     * @param  array<string, array<string, float>>  $totales  "año-mes" => ingresos, gastos, iva_trasladado, iva_retenido, iva_acreditable, isr_retenido
     * @param  array<string, array{isr: ?float, iva: ?float}>  $declarado  lo que ya declaraste por "año-mes" (null = usar lo calculado)
     * @return array<string, array<string, float|int>>
     */
    public static function hojas(array $totales, array $declarado, int $primerAño, int $hastaAño, int $hastaMes): array
    {
        $hojas = [];
        $saldoIva = 0.0;
        for ($año = $primerAño; $año <= $hastaAño; $año++) {
            $acum = ['ingresos' => 0.0, 'gastos' => 0.0, 'isr_retenido' => 0.0, 'pagos' => 0.0];
            for ($mes = 1; $mes <= ($año === $hastaAño ? $hastaMes : 12); $mes++) {
                $t = ($totales["$año-$mes"] ?? []) + array_fill_keys(
                    ['ingresos', 'gastos', 'iva_trasladado', 'iva_retenido', 'iva_acreditable', 'isr_retenido'], 0.0
                );
                $acum['ingresos'] += $t['ingresos'];
                $acum['gastos'] += $t['gastos'];
                $acum['isr_retenido'] += $t['isr_retenido'];

                $base = max(0, round($acum['ingresos']) - round($acum['gastos']));
                $causado = self::isrTarifa($base, $año, $mes);
                $isrCargo = max(0, round($causado - $acum['pagos'] - $acum['isr_retenido']));

                $ivaNeto = round($t['iva_trasladado'] - $t['iva_retenido'] - $t['iva_acreditable']);
                $acreditaSaldo = $ivaNeto > 0 ? min($ivaNeto, $saldoIva) : 0;
                $ivaCargo = max(0, $ivaNeto - $acreditaSaldo);

                $hojas["$año-$mes"] = [
                    'año' => $año, 'mes' => $mes, 'tarifa' => self::añoTarifa($año),
                    'ingresos_periodo' => round($t['ingresos']),
                    'ingresos_acumulados' => round($acum['ingresos']),
                    'gastos_periodo' => round($t['gastos']),
                    'gastos_acumulados' => round($acum['gastos']),
                    'base_gravable' => $base,
                    'isr_causado' => round($causado),
                    'pagos_anteriores' => round($acum['pagos']),
                    'isr_retenido_periodo' => round($t['isr_retenido']),
                    'isr_retenido_acumulado' => round($acum['isr_retenido']),
                    'isr_cargo' => $isrCargo,
                    'iva_actos_16' => round($t['ingresos']),
                    'iva_trasladado' => round($t['iva_trasladado']),
                    'iva_retenido' => round($t['iva_retenido']),
                    'iva_acreditable' => round($t['iva_acreditable']),
                    'iva_neto' => $ivaNeto,
                    'iva_saldo_anterior' => round($saldoIva),
                    'iva_acredita_saldo' => $acreditaSaldo,
                    'iva_cargo' => $ivaCargo,
                ];

                $acum['pagos'] += $declarado["$año-$mes"]['isr'] ?? $isrCargo;
                $saldoIva = $saldoIva - $acreditaSaldo + max(0, -$ivaNeto);
            }
        }

        return $hojas;
    }
}
