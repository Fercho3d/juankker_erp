<?php

namespace Tests\Unit;

use App\Services\FacturaSATParser;
use PHPUnit\Framework\TestCase;

class FacturaSATParserTest extends TestCase
{
    public function test_no_duplica_los_impuestos_de_conceptos_y_comprobante(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" Version="4.0" Fecha="2022-01-26T10:00:00" SubTotal="14000.00" Total="16240.00" Moneda="MXN" TipoDeComprobante="I" MetodoPago="PUE" FormaPago="03">
  <cfdi:Emisor Rfc="SAJU860505762" Nombre="JUAN FERNANDO SALAS" RegimenFiscal="612"/>
  <cfdi:Receptor Rfc="FTM1507038V6" Nombre="CLIENTE" UsoCFDI="G03"/>
  <cfdi:Conceptos>
    <cfdi:Concepto Importe="14000.00">
      <cfdi:Impuestos><cfdi:Traslados><cfdi:Traslado Base="14000.00" Impuesto="002" Importe="2240.00"/></cfdi:Traslados></cfdi:Impuestos>
    </cfdi:Concepto>
  </cfdi:Conceptos>
  <cfdi:Impuestos TotalImpuestosTrasladados="2240.00"><cfdi:Traslados><cfdi:Traslado Base="14000.00" Impuesto="002" Importe="2240.00"/></cfdi:Traslados></cfdi:Impuestos>
  <cfdi:Complemento><tfd:TimbreFiscalDigital UUID="21129230-1F25-4E93-825E-08FC2ED5A027"/></cfdi:Complemento>
</cfdi:Comprobante>
XML;

        $this->assertEquals(2240.0, (new FacturaSATParser)->parse($xml, 'x')['iva_trasladado']);
    }
}
