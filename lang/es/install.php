<?php

return [
    'install' => 'Instalación',
    'step' => 'Etapa',
    'settings' => [
        'title' => 'Parámetros',
        'client_id' => 'ID de cliente',
        'client_secret' => 'Secreto de cliente',
        'hosting_name' => 'Nombre de la empresa',
        'connect' => 'Inicia sesión',
        'migrationwarning' => 'Advertencia: la base de datos no se ha migrado. Por favor, ejecute "php artisan migrate --force" para migrar la base de datos.',
        'detecteddomain' => 'Dominio detectado: :domain. Asegúrese de que el dominio coincida exactamente con el dominio de su licencia (incluido el subdominio, si corresponde).',
        'locales' => 'Idiomas',
        'infolicense' => 'Para obtener un ID de cliente y un secreto de cliente, debes tener una licencia CLIENTXCMS. <a href=":link" target="_blank" class="underline">Haz clic aquí para recuperar tus credenciales de forma gratuita.</a>',
        'eula' => 'Al consultar esta página, acepta los términos de la licencia CLIENTXCMS disponible en clientxcms.com/eula.',
    ],
    'register' => [
        'title' => 'Registro',
        'btn' => 'Crear una cuenta',
        'telemetry' => 'Enviar datos de telemetría anónimos. Esto nos ayuda a mejorar el CMS y corregir los errores. Puede desactivar esta opción más tarde en la configuración.',
    ],
    'summary' => [
        'title' => 'Resumen',
        'btn' => 'Terminar',
    ],
    'submit' => 'Enviar',
    'password' => 'Contraseña',
    'firstname' => 'Nombre',
    'lastname' => 'Apellido',
    'email' => 'Correo electrónico',
    'password_confirmation' => 'Confirmación de la contraseña',
    'authentication' => 'Autenticación',
    'extensions' => 'Extensiones',
];
