<?php

namespace Tests\Unit;

use App\Services\DeclaracionSat;
use PHPUnit\Framework\TestCase;

class DeclaracionSatTest extends TestCase
{
    public function test_isr_causado_coincide_con_el_portal_del_sat_en_enero_2022(): void
    {
        $this->assertSame(1627.0, round(DeclaracionSat::isrTarifa(14700 - 337, 2022, 1)));
    }

    public function test_el_pago_provisional_acumula_y_descuenta_los_anteriores(): void
    {
        $hojas = DeclaracionSat::hojas(['2022-1' => ['ingresos' => 14700, 'gastos' => 337], '2022-2' => ['ingresos' => 14700, 'gastos' => 337]], [], 2022, 2022, 2);

        $this->assertSame([28726.0, 1627.0], [$hojas['2022-2']['base_gravable'], $hojas['2022-2']['pagos_anteriores']]);
    }

    public function test_el_saldo_a_favor_de_iva_se_aplica_en_el_mes_siguiente(): void
    {
        $hojas = DeclaracionSat::hojas(['2022-1' => ['iva_acreditable' => 500], '2022-2' => ['iva_trasladado' => 800]], [], 2022, 2022, 2);

        $this->assertSame(300.0, (float) $hojas['2022-2']['iva_cargo']);
    }
}
