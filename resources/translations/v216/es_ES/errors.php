<?php

/*
 * v2.16 — Traducciones de las páginas de error rediseñadas.
 * Véase resources/translations/v216/README.md.
 */

return [
    'common' => [
        'home' => 'Volver al inicio',
        'dashboard' => 'Volver al panel',
        'support' => 'Contactar al soporte',
        'previous' => 'Volver atrás',
        'status_code' => 'Error :code',
    ],

    '401' => [
        'title' => 'No has iniciado sesión',
        'heading' => 'Autenticación requerida',
        'description' => 'Esta página solo está disponible para usuarios identificados. Inicia sesión y vuelve a intentarlo.',
        'sign_in' => 'Iniciar sesión',
    ],
    '403' => [
        'title' => 'Acceso denegado',
        'heading' => 'Prohibido',
        'description' => 'No tienes permiso para acceder a este recurso. Si crees que es un error, contacta al soporte.',
    ],
    '404' => [
        'title' => 'Página no encontrada',
        'heading' => 'Hemos buscado por todas partes…',
        'description' => 'La página que buscas ha cambiado de sitio o nunca existió. Usa los botones de abajo para retomar el rumbo.',
    ],
    '419' => [
        'title' => 'Tu sesión ha caducado',
        'heading' => 'Sesión caducada',
        'description' => 'Por tu seguridad cerramos la sesión tras un periodo de inactividad. Recarga la página y envía el formulario de nuevo.',
        'reload' => 'Recargar la página',
    ],
    '422' => [
        'title' => 'Solicitud no válida',
        'heading' => 'Algo no cuadra',
        'description' => 'No pudimos procesar tu solicitud porque faltan datos o son incorrectos. Vuelve atrás e inténtalo de nuevo.',
    ],
    '429' => [
        'title' => 'Demasiadas solicitudes',
        'heading' => 'Más despacio',
        'description' => 'Has enviado demasiadas solicitudes en poco tiempo. Espera un momento antes de volver a intentarlo.',
    ],
    '500' => [
        'title' => 'Algo salió mal por nuestra parte',
        'heading' => 'Error interno del servidor',
        'description' => 'Se registró un error inesperado. Nuestro equipo ha sido notificado. Vuelve a intentarlo en unos minutos.',
    ],
    '503' => [
        'title' => 'Estamos en mantenimiento',
        'heading' => 'Servicio no disponible',
        'description' => 'Estamos actualizando la plataforma. El servicio estará disponible en breve. Gracias por tu paciencia.',
    ],
];
