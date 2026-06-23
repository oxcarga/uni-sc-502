(function () { // IIFE para encapsular el código
    // Variables globales
    let resultadoEl = null; // Elemento de resultado

    // Función para convertir la moneda
    async function convertirMoneda() {
        const monto = document.getElementById("monto").value;
        const monedaOrigen = document.getElementById("origen").value;
        const monedaDestino = document.getElementById("destino").value;
    
        if (monto === "" || monto <= 0) {
            return updateError("Por favor ingrese un monto válido");
        }
    
        try {
            const url = `https://open.er-api.com/v6/latest/${monedaOrigen}`;
            const respuesta = await fetch(url);
            const datos = await respuesta.json();
            const tasaCambio = datos.rates[monedaDestino];
            const resultado = monto * tasaCambio;
            updateResultado(`${monto} ${monedaOrigen} = ${resultado.toFixed(2)} ${monedaDestino}`);
        } catch (error) {
            updateError("Error al conectar con la API");
        }
    }

    // Función para actualizar el resultado
    function updateResultado(resultado) {
        if (!resultado) {
            return updateError("Error desconocido");
        }
        const resultadoEl = document.getElementById("resultado");
        resultadoEl.innerHTML = resultado;
    }

    // Función para actualizar el error
    function updateError(error) {
        if (!error) {
            error = "Error desconocido";
        }
        resultadoEl.innerHTML = error;
        console.error(error);
    }

    // Función para inicializar el conversor
    function init() {
        const convertirEl = document.getElementById("convertir");
        convertirEl.addEventListener("click", convertirMoneda);
        resultadoEl = document.getElementById("resultado");
    }

    // Evento load de la ventana
    window.addEventListener("DOMContentLoaded", init);
})();
