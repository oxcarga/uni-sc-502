<?php
// ============================================================
// index.php — Pantalla principal del Sistema de Pedidos Pendientes
// ============================================================

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistema de Pedidos — Delivery/Restaurante</title>
<link rel="stylesheet" href="styles.css">
<!--
  Cargamos Axios desde un CDN. Lo usaremos para las acciones de "avanzar estado" y "eliminar". 
  La acción de "agregar" usará Fetch API en su lugar.
-->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>

  <main class="tarjeta">

    <!-- ======================================================
         ENCABEZADO
         En esta parte nuestro grupo agrego el nombre del sistema y una breve descripción.
         ====================================================== -->
    <header class="encabezado">
      <h1>Pedidos en Cocina</h1>
      <p class="subtitulo">Panel de seguimiento de pedidos para delivery / restaurante</p>
    </header>

    <!-- ======================================================
         FORMULARIO DE NUEVO PEDIDO
         Dos campos: mesa/cliente y producto pedido. El envío de este formulario se intercepta con JavaScript para
         que no recargue la página (ver más abajo, sección "AGREGAR PEDIDO").
         ====================================================== -->
    <form id="form-pedido" class="form-pedido" autocomplete="off">
      <input
        type="text"
        id="input-mesa"
        name="mesa"
        placeholder="Mesa o cliente (ej. Mesa 4)"
        maxlength="40"
      >
      <input
        type="text"
        id="input-producto"
        name="producto"
        placeholder="Producto(s) pedido(s)"
        maxlength="150"
      >
      <button type="submit" class="btn btn-agregar">
        Registrar pedido
      </button>
    </form>

    <!-- Aquí se muestran los mensajes de error de validación -->
    <p id="mensaje-error" class="mensaje-error" role="alert"></p>

    <!-- ======================================================
         RESUMEN / CONTADORES
         Muestra cuántos pedidos hay en cada estado, para tener una vista rápida de la carga de trabajo en cocina.
         ====================================================== -->
    <section class="resumen" id="resumen">
      <span id="contador-pendientes">0 pendientes</span>
      <span class="punto-separador">•</span>
      <span id="contador-entregados">0 entregados</span>
    </section>

    <!-- ======================================================
         LISTA DE PEDIDOS
         Se llena dinámicamente vía JavaScript, uno por cada pedido guardado en $_SESSION['pedidos'].
         ====================================================== -->
    <ul id="lista-pedidos" class="lista-pedidos">
      <!-- Los pedidos se insertan aquí dinámicamente -->
    </ul>

    <p id="estado-vacio" class="estado-vacio" hidden>
      No hay pedidos registrados en este momento.
    </p>
  </main>

<script>
/* =========================================================
   Sistema de Pedidos Pendientes — lógica del frontend
   ---------------------------------------------------------
   Este bloque de JavaScript es el encargado de:
     1. Pedir la lista de pedidos al servidor al cargar la página (acción "listar")
     2. Enviar un nuevo pedido cuando se llena el formulario(acción "agregar", usando Fetch API)
     3. Marcar un pedido como entregado o eliminarlo cuando se hace clic en sus botones (acciones "completar" y "eliminar", usando Axios)
     4. Redibujar la lista completa cada vez que el servidor confirma un cambio, para que la pantalla siempre refleje el estado real guardado en $_SESSION
   ========================================================= */

// -----------------------------------------------------------
// Referencias a los elementos del DOM que vamos a manipular. Las guardamos en variables para no tener que buscarlas en
// el HTML cada vez que las necesitamos.
// -----------------------------------------------------------
const form               = document.getElementById('form-pedido');
const inputMesa          = document.getElementById('input-mesa');
const inputProducto      = document.getElementById('input-producto');
const listaPedidos       = document.getElementById('lista-pedidos');
const mensajeError       = document.getElementById('mensaje-error');
const estadoVacio        = document.getElementById('estado-vacio');
const contadorPendientes = document.getElementById('contador-pendientes');
const contadorEntregados = document.getElementById('contador-entregados');

// Diccionario para traducir el valor interno del estado
// ("pendiente", "entregado") a un texto y un ícono legible
// para el usuario en pantalla.
const INFO_ESTADO = {
  pendiente:   { texto: 'Pendiente', icono: '🕓' },
  entregado:   { texto: 'Entregado', icono: '✅' },
};

/**
 * Muestra un mensaje de error temporal debajo del formulario.
 * Se oculta automáticamente después de unos segundos.
 */
function mostrarError(msg) {
  mensajeError.textContent = msg;
  mensajeError.classList.toggle('visible', Boolean(msg));
  if (msg) {
    setTimeout(() => mostrarError(''), 3500);
  }
}

/**
 * Recibe el arreglo de pedidos (tal como viene del servidor en
 * JSON) y reconstruye toda la lista visual desde cero. También
 * recalcula los contadores de cada estado.
 */
function renderizarPedidos(pedidos) {
  // Limpiamos la lista actual antes de volver a dibujarla.
  listaPedidos.innerHTML = '';

  // Si no hay pedidos, mostramos el mensaje de "lista vacía".
  estadoVacio.hidden = pedidos && pedidos.length > 0;

  // Contadores por estado, para el resumen de arriba.
  let pendientes = 0;
  let entregados = 0;

  pedidos.forEach((pedido, indice) => {
    // Sumamos al contador correspondiente según el estado.
    if (pedido.estado === 'pendiente') pendientes++;
    else if (pedido.estado === 'entregado') entregados++;

    const info = INFO_ESTADO[pedido.estado] || INFO_ESTADO.pendiente;
    const yaEntregado = pedido.estado === 'entregado';

    // Creamos el elemento <li> del pedido con toda su
    // información y sus botones de acción.
    const li = document.createElement('li');
    li.className = 'pedido pedido-' + pedido.estado;

    li.innerHTML = `
      <span class="pedido-icono">${info.icono}</span>
      <div class="pedido-info">
        <span class="pedido-mesa">${pedido.mesa}</span>
        <span class="pedido-producto">${pedido.producto}</span>
      </div>
      <span class="pedido-estado-etiqueta pedido-estado-${pedido.estado}">${info.texto}</span>
      <span class="pedido-acciones">
        ${yaEntregado
          ? ''
          : `<button class="btn btn-completar" data-indice="${indice}" title="Marcar como entregado">Entregado</button>`}
        <button class="btn btn-eliminar" data-indice="${indice}" title="Eliminar pedido">Eliminar</button>
      </span>
    `;

    listaPedidos.appendChild(li);
  });

  // Actualizamos el texto de los contadores del resumen.
  contadorPendientes.textContent = `${pendientes} pendiente${pendientes === 1 ? '' : 's'}`;
  contadorEntregados.textContent = `${entregados} entregado${entregados === 1 ? '' : 's'}`;
}

/* ---------------------------------------------------------
   CARGA INICIAL DE PEDIDOS (acción "listar")
   ---------------------------------------------------------
   Al abrir la página, pedimos al servidor la lista actual de pedidos guardados en la sesión. Usamos Fetch API aquí porque
   es una simple lectura de datos, similar a la operación de "agregar" que también usa Fetch.
   --------------------------------------------------------- */
async function cargarPedidos() {
  try {
    const datosFormulario = new URLSearchParams();
    datosFormulario.append('accion', 'listar');

    const respuesta = await fetch('api.php', {
      method: 'POST',
      body: datosFormulario
    });
    const data = await respuesta.json();

    if (data.exito) {
      renderizarPedidos(data.pedidos);
    } else {
      mostrarError(data.mensaje || 'No se pudieron cargar los pedidos.');
    }
  } catch (error) {
    mostrarError('Problema de conexión al cargar los pedidos.');
  }
}

/* ---------------------------------------------------------
   REGISTRAR NUEVO PEDIDO (acción "agregar") — usa Fetch API
   ---------------------------------------------------------
   Se ejecuta cuando el usuario envía el formulario. Primero validamos en el cliente (que ningún campo esté vacío) y
   luego enviamos los datos al servidor de forma asíncrona, sin recargar la página.
   --------------------------------------------------------- */
form.addEventListener('submit', async (evento) => {
  // Evitamos el comportamiento por defecto del formulario
  // (que normalmente recargaría la página).
  evento.preventDefault();

  const mesa = inputMesa.value.trim();
  const producto = inputProducto.value.trim();

  // Validación en el cliente: primera línea de defensa antes
  // de molestar al servidor con datos incompletos.
  if (mesa === '' || producto === '') {
    mostrarError('Completa la mesa/cliente y el producto antes de registrar.');
    return;
  }

  try {
    const datosFormulario = new URLSearchParams();
    datosFormulario.append('accion', 'agregar');
    datosFormulario.append('mesa', mesa);
    datosFormulario.append('producto', producto);

    const respuesta = await fetch('api.php', {
      method: 'POST',
      body: datosFormulario
    });
    const data = await respuesta.json();

    if (data.exito) {
      // Limpiamos el formulario y volvemos a dibujar la lista
      // con los datos actualizados que regresó el servidor.
      inputMesa.value = '';
      inputProducto.value = '';
      renderizarPedidos(data.pedidos);
    } else {
      mostrarError(data.mensaje || 'No se pudo registrar el pedido.');
    }
  } catch (error) {
    mostrarError('Problema de conexión al registrar el pedido.');
  }
});

/* ---------------------------------------------------------
   MARCAR COMO ENTREGADO Y ELIMINAR PEDIDO — usan Axios
   --------------------------------------------------------- */
listaPedidos.addEventListener('click', async (evento) => {
  const boton = evento.target.closest('button');
  if (!boton) return;

  const indice = boton.dataset.indice;

  try {
    // Caso 1: se hizo clic en "Entregado" (marcar como completado)
    if (boton.classList.contains('btn-completar')) {
      const params = new URLSearchParams();
      params.append('accion', 'completar');
      params.append('indice', indice);

      // Axios envía la petición y ya nos da la respuesta parseada directamente en "data" (no hace falta
      // llamar a .json() como con Fetch).
      const { data } = await axios.post('api.php', params);

      if (data.exito) {
        renderizarPedidos(data.pedidos);
      } else {
        mostrarError(data.mensaje || 'No se pudo marcar el pedido como entregado.');
      }
    }

    // Caso 2: se hizo clic en "eliminar" (✕)
    if (boton.classList.contains('btn-eliminar')) {
      const params = new URLSearchParams();
      params.append('accion', 'eliminar');
      params.append('indice', indice);

      const { data } = await axios.post('api.php', params);

      if (data.exito) {
        renderizarPedidos(data.pedidos);
      } else {
        mostrarError(data.mensaje || 'No se pudo eliminar el pedido.');
      }
    }
  } catch (error) {
    mostrarError('Problema de conexión con el servidor (Axios).');
  }
});

// -----------------------------------------------------------
// Llamada inicial: en cuanto carga el script, pedimos la lista de pedidos existente para mostrarla de inmediato.
// -----------------------------------------------------------
cargarPedidos();
</script>
</body>
</html>
