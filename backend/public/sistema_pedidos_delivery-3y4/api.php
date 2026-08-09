<?php
/**
 * ============================================================
 *  api.php — Sistema de Pedidos Pendientes (Delivery/Restaurante)
 * ============================================================
 *
 * Este archivo tiene como objetivo crear pedidos, marcarlos como entregados y eliminarlos.
 *
 * Cada pedido tiene 2 estados posibles (igual que pide la especificación original de la práctica: agregar, completar,
 * eliminar, listar):
 *   1. "pendiente"  -> el pedido está en cocina, aún no sale
 *   2. "entregado"  -> el pedido ya fue entregado al cliente
 */

// -----------------------------------------------------------
// Iniciamos la sesión de PHP. Esto es lo que nos permite "recordar" los pedidos entre una petición y otra, sin
// necesidad de una base de datos. Cada usuario/navegador tiene su propia sesión independiente.
// -----------------------------------------------------------
session_start();

header('Content-Type: application/json; charset=utf-8');

// -----------------------------------------------------------
// Manejo de errores personalizado (requisito de la práctica).
// -----------------------------------------------------------
set_error_handler(function ($severidad, $mensaje, $archivo, $linea) {
    throw new ErrorException($mensaje, 0, $severidad, $archivo, $linea);
});

/**
 * Función auxiliar para responder al frontend. Convierte el arreglo $data a JSON, lo imprime y termina
 * la ejecución del script (exit), para no seguir corriendo código después de haber respondido.
 */
function responder($data) {
    echo json_encode($data);
    exit;
}

/**
 * Inicializa el arreglo de pedidos en la sesión si todavía no existe (por ejemplo, la primera vez que el usuario entra
 * al sistema). Se agregan 2 pedidos de ejemplo para que la pantalla no se vea vacía al inicio.
 */
function inicializarPedidos() {
    if (!isset($_SESSION['pedidos']) || !is_array($_SESSION['pedidos'])) {
        $_SESSION['pedidos'] = [
            [
                "mesa"     => "Mesa 3",
                "producto" => "2 hamburguesas, 1 papas fritas",
                "estado"   => "pendiente"
            ],
            [
                "mesa"     => "Mesa 5",
                "producto" => "1 pizza mediana",
                "estado"   => "entregado"
            ],
        ];
    }
}

// -----------------------------------------------------------
// Bloque principal: aquí se decide qué acción ejecutar según el parámetro "accion" que llega por POST desde el frontend.
// Todo el bloque va envuelto en try/catch para capturar cualquier error inesperado
// -----------------------------------------------------------
try {

    // Nos aseguramos de que siempre exista el arreglo de pedidos antes de hacer cualquier operación sobre él.
    inicializarPedidos();

    // Leemos qué acción se solicitó. Si no viene ninguna, usamos cadena vacía para caer en el "default" más abajo.
    $accion = $_POST['accion'] ?? '';

    switch ($accion) {

        case 'agregar': {
            $mesa     = trim($_POST['mesa'] ?? '');
            $producto = trim($_POST['producto'] ?? '');
            if ($mesa === '' || $producto === '') {
                responder([
                    "exito"   => false,
                    "mensaje" => "Debes indicar la mesa/cliente y el producto del pedido."
                ]);
            }

            if (mb_strlen($mesa) > 40 || mb_strlen($producto) > 150) {
                responder([
                    "exito"   => false,
                    "mensaje" => "El texto de mesa o producto es demasiado largo."
                ]);
            }


            $_SESSION['pedidos'][] = [
                "mesa"     => htmlspecialchars($mesa, ENT_QUOTES, 'UTF-8'),
                "producto" => htmlspecialchars($producto, ENT_QUOTES, 'UTF-8'),
                "estado"   => "pendiente"
            ];


            responder([
                "exito"   => true,
                "pedidos" => $_SESSION['pedidos']
            ]);
            break;
        }

        // ---------------------------------------------------
        // ACCIÓN: completar
        // ---------------------------------------------------
        case 'completar': {

            $indice = isset($_POST['indice']) ? (int) $_POST['indice'] : -1;

            // Verificamos que el pedido exista antes de tocarlo.
            if (!isset($_SESSION['pedidos'][$indice])) {
                responder([
                    "exito"   => false,
                    "mensaje" => "El pedido indicado no existe."
                ]);
            }

            // Si ya estaba entregado, no hay nada que cambiar.
            if ($_SESSION['pedidos'][$indice]['estado'] === 'entregado') {
                responder([
                    "exito"   => false,
                    "mensaje" => "Este pedido ya fue entregado."
                ]);
            }

            $_SESSION['pedidos'][$indice]['estado'] = 'entregado';

            responder([
                "exito"   => true,
                "pedidos" => $_SESSION['pedidos']
            ]);
            break;
        }

        // ---------------------------------------------------
        // ACCIÓN: eliminar
        // ---------------------------------------------------
        case 'eliminar': {

            $indice = isset($_POST['indice']) ? (int) $_POST['indice'] : -1;

            if (!isset($_SESSION['pedidos'][$indice])) {
                responder([
                    "exito"   => false,
                    "mensaje" => "El pedido indicado no existe."
                ]);
            }

            // array_splice elimina 1 elemento en la posición
            // $indice y reordena los índices del arreglo.
            array_splice($_SESSION['pedidos'], $indice, 1);

            responder([
                "exito"   => true,
                "pedidos" => $_SESSION['pedidos']
            ]);
            break;
        }

        // ---------------------------------------------------
        // ACCIÓN: listar
        // ---------------------------------------------------
        case 'listar': {
            responder([
                "exito"   => true,
                "pedidos" => $_SESSION['pedidos']
            ]);
            break;
        }

        // ---------------------------------------------------
        // Si la acción no coincide con ninguna de las
        // anteriores, respondemos con un error claro.
        // ---------------------------------------------------
        default: {
            responder([
                "exito"   => false,
                "mensaje" => "Acción no reconocida: '$accion'."
            ]);
        }
    }

} catch (Throwable $e) {
    // Este bloque atrapa cualquier error inesperado que haya ocurrido en el try (incluyendo los que set_error_handler
    responder([
        "exito"   => false,
        "mensaje" => "Ocurrió un error inesperado en el servidor: " . $e->getMessage()
    ]);
}
