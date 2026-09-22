<?php

/*
| Envío diario automático a prospectos (`crm:enviar-diario`). Sólo corre para
| la organización dueña del buzón (`CRM_ENVIO_ORGANIZACION`) y con la plantilla
| aprobada; `:lista` se reemplaza según el sector del prospecto y `:empresa`
| con su nombre como va en un saludo.
*/

return [
    'envio_diario' => [
        'por_dia' => (int) env('CRM_AUTOENVIO_POR_DIA', 10),
        'pausa_segundos' => (int) env('CRM_AUTOENVIO_PAUSA', 20),

        'asunto' => 'Juancker · Desarrollo de software a la medida',

        'cuerpo' => <<<'TXT'
            Buen día, equipo :empresa:

            Soy Fernando Salas, ingeniero en programación; actualmente estoy liderando mi empresa Juancker.

            Desarrollamos sistemas que concentran :lista en un solo lugar, ya sea partiendo de un ERP que tenemos en producción o construyéndolos a la medida de su forma de trabajar. La idea es automatizar su operación para que el equipo deje de capturar lo mismo dos veces.

            Al estar aquí mismo en Aguascalientes, la atención es directa y en persona.

            Me gustaría agendar una reunión breve, en sus oficinas o en línea, para conocer cómo trabajan hoy y ver en qué les podemos ayudar.

            Quedo atento a sus comentarios.

            Saludos,
            Fernando Salas
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
                Buen día, equipo :empresa:

                Les escribí hace unos días para presentarme: soy Fernando Salas, de Juancker, y desarrollamos sistemas que concentran :lista en un solo lugar.

                Sé que el correo se pierde entre lo urgente, por eso lo retomo. ¿Tendrían un espacio para una reunión breve, en persona o en línea, en los próximos días?

                Quedo atento a sus comentarios.

                Saludos,
                Fernando Salas
                Juancker · Software a la medida
                Tel. 449 932 6936
                TXT,
            3 => <<<'TXT'
                Buen día, equipo :empresa:

                Este es mi último correo, no quiero ser insistente.

                Si más adelante necesitan ordenar :lista, con gusto los visito: basta con responder este correo o llamar al 449 932 6936.

                Que tengan buena semana.

                Fernando Salas
                Juancker · Software a la medida
                TXT,
        ],
    ],
];
