<?php

return [
    'install' => 'Instalación',
    'step' => 'Paso',
    'settings' => [
        'title' => 'Configuración',
        'client_id' => 'ID de cliente',
        'client_secret' => 'Secreto de cliente',
        'hosting_name' => 'Nombre de la empresa',
        'connect' => 'Conectar',
        'locales' => 'Idiomas',
        'infolicense' => 'Para obtener un ID de cliente y un secreto de cliente, debes tener una licencia de CLIENTXCMS. <a href=":link" target="_blank" class="underline">Haz clic aquí para obtener tus credenciales gratis.</a>',
        'migrationwarning' => 'Advertencia: la base de datos no ha sido migrada. Ejecuta "php artisan migrate --force --seed" para migrar la base de datos.',
        'detecteddomain' => 'Dominio detectado: :domain. Asegúrate de que el dominio coincida exactamente con el de tu licencia (incluido el subdominio si aplica).',
        'eula' => 'Al visitar esta página, aceptas los términos de la licencia de CLIENTXCMS disponible en clientxcms.com/eula.',
    ],
    'register' => [
        'title' => 'Registro',
        'btn' => 'Crear cuenta',
        'telemetry' => 'Enviar datos de telemetría anonimizados. Esto nos ayuda a mejorar el CMS y corregir errores. Puedes desactivar esta opción más tarde en la configuración.',
    ],
    'summary' => [
        'title' => 'Resumen',
        'btn' => 'Finalizar',
    ],
    'submit' => 'Enviar',
    'password' => 'Contraseña',
    'firstname' => 'Nombre',
    'lastname' => 'Apellido',
    'email' => 'Correo electrónico',
    'password_confirmation' => 'Confirmación de contraseña',
    'authentication' => 'Autenticación',
    'extensions' => 'Extensiones',
    'departmentsseeder' => [
        'general' => [
            'name' => 'General',
            'description' => 'Departamento general',
        ],
        'billing' => [
            'name' => 'Facturación',
            'description' => 'Departamento de facturación',
        ],
        'technical' => [
            'name' => 'Técnico',
            'description' => 'Departamento técnico',
        ],
        'sales' => [
            'name' => 'Ventas',
            'description' => 'Departamento comercial',
        ],
    ],
    'security_questions' => [
        'pet_name' => '¿Cuál es el nombre de tu primera mascota?',
        'birth_city' => '¿En qué ciudad naciste?',
        'mother_maiden_name' => '¿Cuál es el apellido de soltera de tu madre?',
        'first_school' => '¿Cuál es el nombre de tu primera escuela?',
        'favorite_movie' => '¿Cuál es tu película favorita?',
        'childhood_nickname' => '¿Cuál era tu apodo de la infancia?',
    ],
];
