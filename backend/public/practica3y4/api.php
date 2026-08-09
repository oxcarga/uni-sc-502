<?php

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['tareas'])) {
    $_SESSION['tareas'] = [];
}

set_error_handler(function($errno, $errstr){
    throw new Exception($errstr);
});

function responder($exito, $mensaje = '', $tareas = [])
{
    echo json_encode([
        'exito' => $exito,
        'mensaje' => $mensaje,
        'tareas' => $tareas
    ]);

    exit;
}

try {

    $accion = $_POST['accion'] ?? '';

    switch($accion){

        case 'agregar':

            $nombre = trim($_POST['nombre'] ?? '');

            if(empty($nombre)){
                responder(false, 'La tarea está vacía.');
            }

            $_SESSION['tareas'][] = [
                'nombre' => htmlspecialchars($nombre),
                'completada' => false
            ];

            responder(
                true,
                'Tarea agregada.',
                $_SESSION['tareas']
            );

            break;

        case 'listar':

            responder(
                true,
                '',
                $_SESSION['tareas']
            );

            break;

        case 'completar':

            $indice = $_POST['indice'] ?? -1;

            if(!isset($_SESSION['tareas'][$indice])){

                responder(
                    false,
                    'Tarea no encontrada.'
                );
            }

            $_SESSION['tareas'][$indice]['completada'] = true;

            responder(
                true,
                'Tarea completada.',
                $_SESSION['tareas']
            );

            break;

        case 'eliminar':

            $indice = $_POST['indice'] ?? -1;

            if(!isset($_SESSION['tareas'][$indice])){

                responder(
                    false,
                    'Tarea no encontrada.'
                );
            }

            array_splice(
                $_SESSION['tareas'],
                $indice,
                1
            );

            responder(
                true,
                'Tarea eliminada.',
                $_SESSION['tareas']
            );

            break;

        default:

            responder(
                false,
                'Acción no válida.'
            );
    }

} catch(Exception $e){

    responder(
        false,
        $e->getMessage()
    );
}
?>