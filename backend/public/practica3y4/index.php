<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mini Sistema de Gestión de Tareas</title>

    styles.css
</head>
<body>
<div class="container">

    <h1>Mini Sistema de Gestión de Tareas</h1>

    <form id="formTarea">

        <input
            type="text"
            id="nombre"
            placeholder="Ingrese una nueva tarea">

        <button type="submit">
            Agregar
        </button>

    </form>

    <p id="mensaje"></p>

    <ul id="listaTareas"></ul>

</div>

<script>

const lista = document.getElementById("listaTareas");
const mensaje = document.getElementById("mensaje");

async function cargarTareas() {

    const datos = new FormData();
    datos.append("accion", "listar");

    const respuesta = await fetch("api.php", {
        method: "POST",
        body: datos
    });

    const resultado = await respuesta.json();

    renderizarTareas(resultado.tareas);
}

function renderizarTareas(tareas) {

    lista.innerHTML = "";

    tareas.forEach((tarea, indice) => {

        const li = document.createElement("li");

        li.innerHTML = `
            <span class="${tarea.completada ? 'completada' : ''}">
                ${tarea.nombre}
            </span>

            <div>

                ${
                    !tarea.completada
                    ? `<button onclick="completarTarea(${indice})">Completar</button>`
                    : ''
                }

                <button onclick="eliminarTarea(${indice})">Eliminar</button>

            </div>
        `;

        lista.appendChild(li);
    });
}

function mostrarMensaje(texto, exito = false) {

    mensaje.textContent = texto;

    mensaje.style.color =
        exito ? "green" : "red";

    setTimeout(() => {
        mensaje.textContent = "";
    }, 3000);
}

document.getElementById("formTarea")
.addEventListener("submit", async function(e){

    e.preventDefault();

    const nombre =
    document.getElementById("nombre")
    .value
    .trim();

    if(nombre === ""){

        mostrarMensaje(
            "Debe ingresar una tarea."
        );

        return;
    }

    const datos = new FormData();

    datos.append("accion", "agregar");
    datos.append("nombre", nombre);

    const respuesta = await fetch(
        "api.php",
        {
            method: "POST",
            body: datos
        }
    );

    const resultado =
    await respuesta.json();

    procesarRespuesta(resultado);

    document.getElementById("nombre").value = "";
});

function procesarRespuesta(resultado){

    if(resultado.exito){

        renderizarTareas(resultado.tareas);

        if(resultado.mensaje){

            mostrarMensaje(
                resultado.mensaje,
                true
            );
        }

    } else {

        mostrarMensaje(
            resultado.mensaje
        );
    }
}

async function completarTarea(indice){

    const datos = new FormData();

    datos.append("accion", "completar");
    datos.append("indice", indice);

    const respuesta = await fetch(
        "api.php",
        {
            method: "POST",
            body: datos
        }
    );

    const resultado =
    await respuesta.json();

    procesarRespuesta(resultado);
}

async function eliminarTarea(indice){

    const datos = new FormData();

    datos.append("accion", "eliminar");
    datos.append("indice", indice);

    const respuesta = await fetch(
        "api.php",
        {
            method: "POST",
            body: datos
        }
    );

    const resultado =
    await respuesta.json();

    procesarRespuesta(resultado);
}

cargarTareas();

</script>

</body>
</html>