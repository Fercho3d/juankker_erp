<?php

namespace App\Services;

use Exception;
use SimpleXMLElement;

class FacturaSATParser
{
    private const NS_CFDI3 = 'http://www.sat.gob.mx/cfd/3';

    private const NS_CFDI4 = 'http://www.sat.gob.mx/cfd/4';

    private const NS_TFD = 'http://www.sat.gob.mx/TimbreFiscalDigital';

    /**
     * Parsea un archivo XML CFDI 3.3 o 4.0 del SAT.
     * $filenameHint: nombre del archivo (sin extensión) para usar UUID del filename como fallback.
     */
    public function parse(string $xmlContent, string $filenameHint = ''): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = array_map(fn ($e) => trim($e->message), libxml_get_errors());
            libxml_clear_errors();
            throw new Exception('XML inválido: '.implode(', ', $errors));
        }

        // Registrar namespaces para XPath
        $xml->registerXPathNamespace('cfdi3', self::NS_CFDI3);
        $xml->registerXPathNamespace('cfdi4', self::NS_CFDI4);
        $xml->registerXPathNamespace('tfd', self::NS_TFD);

        // UUID: primero por XPath (más fiable), luego por nombre de archivo
        $uuid = $this->extractUUID($xml, $filenameHint);
        if (empty($uuid)) {
            throw new Exception('No se encontró el UUID (Folio Fiscal) en el XML.');
        }

        // Atributos raíz
        $subtotal = (float) ($xml['SubTotal'] ?? 0);
        $descuento = (float) ($xml['Descuento'] ?? 0);
        $total = (float) ($xml['Total'] ?? 0);
        $moneda = (string) ($xml['Moneda'] ?? 'MXN');
        $tipoCambio = (float) ($xml['TipoCambio'] ?? 1);
        $metodoPago = (string) ($xml['MetodoPago'] ?? '');
        $formaPago = (string) ($xml['FormaPago'] ?? '');
        $tipoComprobante = strtoupper((string) ($xml['TipoDeComprobante'] ?? 'I'));
        $fechaStr = (string) ($xml['Fecha'] ?? '');
        $fecha = substr($fechaStr, 0, 10);

        // Emisor y Receptor: XPath primero, luego acceso directo
        $emisorNombre = $this->xpathStr($xml, ['//cfdi3:Emisor/@Nombre',   '//cfdi4:Emisor/@Nombre',   '//@NombreEmisor']);
        $emisorRfc = $this->xpathStr($xml, ['//cfdi3:Emisor/@Rfc',      '//cfdi4:Emisor/@Rfc']);
        $emisorRegimen = $this->xpathStr($xml, ['//cfdi3:Emisor/@RegimenFiscal', '//cfdi4:Emisor/@RegimenFiscal']);
        $receptorNombre = $this->xpathStr($xml, ['//cfdi3:Receptor/@Nombre', '//cfdi4:Receptor/@Nombre']);
        $receptorRfc = $this->xpathStr($xml, ['//cfdi3:Receptor/@Rfc',    '//cfdi4:Receptor/@Rfc']);
        $usoCfdi = $this->xpathStr($xml, ['//cfdi3:Receptor/@UsoCFDI', '//cfdi4:Receptor/@UsoCFDI']);

        // Si XPath no encontró nada, acceso por children con namespace detectado
        if (empty($emisorRfc)) {
            $ns = $xml->getNamespaces(true);
            $cfdiNs = $ns['cfdi'] ?? ($ns[''] ?? '');
            $emisor = $xml->children($cfdiNs)->Emisor ?? $xml->Emisor ?? null;
            $receptor = $xml->children($cfdiNs)->Receptor ?? $xml->Receptor ?? null;
            $emisorRfc = strtoupper((string) ($emisor['Rfc'] ?? ''));
            $emisorNombre = (string) ($emisor['Nombre'] ?? '');
            $emisorRegimen = (string) ($emisor['RegimenFiscal'] ?? '');
            $receptorRfc = strtoupper((string) ($receptor['Rfc'] ?? ''));
            $receptorNombre = (string) ($receptor['Nombre'] ?? '');
            $usoCfdi = (string) ($receptor['UsoCFDI'] ?? '');
        }

        // Impuestos
        [$ivaTrasladado, $ivaRetenido, $isrRetenido] = $this->extractImpuestos($xml);

        return [
            'uuid' => strtoupper($uuid),
            'tipo_comprobante' => $tipoComprobante,
            'fecha_emision' => $fecha,
            'año' => (int) substr($fecha, 0, 4),
            'mes' => (int) substr($fecha, 5, 2),
            'rfc_emisor' => strtoupper($emisorRfc),
            'nombre_emisor' => $emisorNombre,
            'regimen_fiscal_emisor' => $emisorRegimen,
            'rfc_receptor' => strtoupper($receptorRfc),
            'nombre_receptor' => $receptorNombre,
            'uso_cfdi' => $usoCfdi,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'iva_trasladado' => $ivaTrasladado,
            'iva_retenido' => $ivaRetenido,
            'isr_retenido' => $isrRetenido,
            'total' => $total,
            'moneda' => $moneda,
            'tipo_cambio' => $tipoCambio,
            'metodo_pago' => $metodoPago ?: null,
            'forma_pago' => $formaPago ?: null,
        ];
    }

    // -------------------------------------------------------------------------

    private function extractUUID(SimpleXMLElement $xml, string $filenameHint): string
    {
        // 1. XPath con namespace tfd
        $result = $xml->xpath('//tfd:TimbreFiscalDigital/@UUID');
        if ($result && isset($result[0]) && (string) $result[0] !== '') {
            return (string) $result[0];
        }

        // 2. XPath sin namespace (por si el archivo no declara el prefijo tfd correctamente)
        $result = $xml->xpath('//*[local-name()="TimbreFiscalDigital"]/@UUID');
        if ($result && isset($result[0]) && (string) $result[0] !== '') {
            return (string) $result[0];
        }

        // 3. Recorrer Complemento buscando cualquier atributo UUID
        $result = $xml->xpath('//*[local-name()="Complemento"]//*/@UUID');
        if ($result && isset($result[0]) && (string) $result[0] !== '') {
            return (string) $result[0];
        }

        // 4. Buscar UUID en cualquier parte del documento
        $result = $xml->xpath('//@UUID');
        foreach ($result as $val) {
            $v = (string) $val;
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $v)) {
                return $v;
            }
        }

        // 5. Fallback: el nombre del archivo del paquete SAT SIEMPRE es el UUID
        if (preg_match('/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $filenameHint, $m)) {
            return $m[1];
        }

        return '';
    }

    private function extractImpuestos(SimpleXMLElement $xml): array
    {
        $ivaTrasladado = 0.0;
        $ivaRetenido = 0.0;
        $isrRetenido = 0.0;

        // Sólo el resumen de Impuestos del comprobante: los de cada concepto repiten los mismos
        // importes y sumar ambos duplicaba el IVA y las retenciones. Sin resumen, se suman los conceptos.
        $nodos = $xml->xpath('/*/*[local-name()="Impuestos"]') ?: $xml->xpath('//*[local-name()="Concepto"]/*[local-name()="Impuestos"]');

        foreach ($nodos as $impuestos) {
            foreach ($impuestos->xpath('*[local-name()="Traslados"]/*[local-name()="Traslado"]') as $t) {
                if ((string) ($t['Impuesto'] ?? '') === '002') {
                    $ivaTrasladado += (float) ($t['Importe'] ?? 0);
                }
            }
            foreach ($impuestos->xpath('*[local-name()="Retenciones"]/*[local-name()="Retencion"]') as $r) {
                $importe = (float) ($r['Importe'] ?? 0);
                match ((string) ($r['Impuesto'] ?? '')) {
                    '001' => $isrRetenido += $importe,
                    '002' => $ivaRetenido += $importe,
                    default => null,
                };
            }
        }

        return [$ivaTrasladado, $ivaRetenido, $isrRetenido];
    }

    /**
     * Evalúa una lista de expresiones XPath en orden y devuelve el primer resultado no vacío.
     */
    private function xpathStr(SimpleXMLElement $xml, array $expressions): string
    {
        foreach ($expressions as $expr) {
            $r = $xml->xpath($expr);
            if ($r && isset($r[0]) && (string) $r[0] !== '') {
                return (string) $r[0];
            }
        }

        return '';
    }
}
