<?php

return [
  'install' => 'Instalación',
  'step' => 'Escenario',
  'settings' => [
    'title' => 'Ajustes',
    'client_id' => 'ID de cliente',
    'client_secret' => 'Confidencialidad del cliente',
    'hosting_name' => 'nombre de empresa',
    'connect' => 'Acceso',
    'locales' => 'IDIOMAS',
    'infolicense' => 'Para obtener un ID de cliente y un secreto de cliente, debe tener una licencia CLIENTXCMS. <a href=" :link " target="_blank" class="underline">Haga clic aquí para recuperar sus datos de inicio de sesión de forma gratuita.</a>',
    'migrationwarning' => 'Advertencia: la base de datos no ha sido migrada. Ejecute "php articulado migrar --force --seed" para migrar la base de datos.',
    'detecteddomain' => 'Dominio detectado: :domain. Asegúrese de que el dominio coincida exactamente con su licencia (incluido el subdominio, si corresponde).',
    'eula' => 'Al ver esta página, acepta los términos de la licencia CLIENTXCMS disponible en clientxcms.com/eula.',
  ],
  'register' => [
    'title' => 'Registro',
    'btn' => 'Crear una cuenta',
    'telemetry' => 'Envíe datos de telemetría anonimizados. Esto nos ayuda a mejorar el CMS y corregir errores. Puede desactivar esta opción más adelante en la configuración.',
  ],
  'summary' => [
    'title' => 'Resumen',
    'btn' => 'para terminar',
  ],
  'submit' => 'Enviar',
  'password' => 'Contraseña',
  'firstname' => 'Nombre de pila',
  'lastname' => 'Apellido',
  'email' => 'Correo electrónico',
  'password_confirmation' => 'Confirmación de contraseña',
  'authentication' => 'Autenticación',
  'extensions' => 'Extensiones',
  'departmentsseeder' => [
    'general' => [
      'name' => 'General',
      'description' => 'Departamento General',
    ],
    'billing' => [
      'name' => 'Facturación',
      'description' => 'Departamento de Facturación',
    ],
    'technical' => [
      'name' => 'Técnico',
      'description' => 'Departamento técnico',
    ],
    'sales' => [
      'name' => 'Comercial',
      'description' => 'Departamento comercial',
    ],
  ],
  'security_questions' => [
    'pet_name' => '¿Cómo se llama tu primera mascota?',
    'birth_city' => '¿En qué ciudad naciste?',
    'mother_maiden_name' => '¿Cuál es el apellido de soltera de tu madre?',
    'first_school' => '¿Cómo se llama tu primera escuela?',
    'favorite_movie' => '¿Cuál es tu película favorita?',
    'childhood_nickname' => '¿Cuál era tu apodo de infancia?',
  ],
];
