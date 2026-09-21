<?php

/*
| Envío diario automático a prospectos (`crm:enviar-diario`). Sólo corre para
| la organización dueña del buzón (`CRM_ENVIO_ORGANIZACION`) y con la plantilla
| aprobada; `:lista` se reemplaza según el sector del prospecto.
*/

return [
    'envio_diario' => [
        'por_dia' => (int) env('CRM_AUTOENVIO_POR_DIA', 10),
        'pausa_segundos' => (int) env('CRM_AUTOENVIO_PAUSA', 20),

        'asunto' => 'Juancker · Desarrollo de software a la medida',

        'cuerpo' => <<<'TXT'
            Buen día:

            Le escribo de Juancker, soluciones de software para empresas, aquí en Aguascalientes.

            ¿La administración y el papeleo los llevan con hojas de Excel, o ya trabajan con algún sistema?

            Desarrollo sistemas que llevan :lista en un mismo lugar, a la medida o partiendo de un ERP que ya tengo en producción. La idea es automatizar su operación para que el equipo deje de capturar lo mismo dos veces.

            Si quiere verlo funcionando, puede crear su cuenta y probar mi ERP 30 días sin costo y sin tarjeta: https://erp.juancker.com/register

            Si le interesa, puede contactarme: le dejo mis datos más abajo.

            Saludos,
            Juan Fernando Salas
            Juancker · Software a la medida
            Tel. 449 932 6936 · contacto@juancker.com
            https://juancker.com
            TXT,

        // Buzones que no llegan a quien decide una compra.
        'omitir_correos' => ['reclutamiento', 'rrhh', 'rh@', 'empleo', 'vacante', 'curriculum', 'bolsadetrabajo'],

        // Sólo a estos sectores: los demás (restaurantes, iglesias, talleres) no son perfil.
        'listas' => [
            'Construcción' => 'compras, inventario, nómina y costos por obra',
            'Manufactura' => 'compras, producción, inventario y facturación CFDI 4.0',
            'Comercio al por mayor' => 'inventario, compras, ventas y facturación CFDI 4.0',
            'Comercio al por menor' => 'punto de venta, inventario, compras y facturación CFDI 4.0',
            'Transporte' => 'gastos por unidad, cuentas por cobrar y facturación CFDI 4.0',
        ],
    ],

    /*
    | Seguimiento automático (`crm:seguimiento`) a quien recibió el primer correo
    | y no contestó: sale en el mismo hilo, corto y con una sola pregunta. Se
    | detiene si el prospecto responde, cambia de etapa o alguien lo atiende.
    */
    'seguimiento' => [
        'por_dia' => (int) env('CRM_SEGUIMIENTO_POR_DIA', 10),

        // Días desde el primer correo para cada toque.
        'dias' => [2 => 3, 3 => 7],

        'cuerpos' => [
            2 => <<<'TXT'
                Buen día:

                Le escribí hace unos días sobre cómo llevan :lista en su empresa. Sé que el correo se pierde entre lo urgente, así que lo subo de nuevo.

                Una sola pregunta: ¿hoy eso lo llevan en Excel o ya con algún sistema?

                Con esa respuesta le digo en dos líneas si le conviene o no lo que hago, sin compromiso.

                Saludos,
                Juan Fernando Salas
                Juancker · Software a la medida
                Tel. 449 932 6936
                TXT,
            3 => <<<'TXT'
                Buen día:

                Último correo de mi parte, no quiero ser insistente.

                Si en algún momento el control de :lista se vuelve un dolor de cabeza, aquí estoy: un mensaje a este correo o al 449 932 6936 y lo platicamos.

                Que tenga buena semana.

                Juan Fernando Salas
                Juancker · Software a la medida
                TXT,
        ],
    ],
];
