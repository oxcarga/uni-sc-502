async function convertirMoneda() {
    const monto = document.getElementById("monto").value;
    const monedaOrigen = document.getElementById("origen").value;
    const monedaDestino = document.getElementById("destino").value;
    const resultadoTexto = document.getElementById("resultado");

    if (monto === "" || monto <= 0) {
        resultadoTexto.innerHTML = "Por favor ingrese un monto válido";
        return;
    }

    try {
        const url = `https://open.er-api.com/v6/latest/${monedaOrigen}`;

        const respuesta = await fetch(url);
        const datos = await respuesta.json();

        const tasaCambio = datos.rates[monedaDestino];

        const resultado = monto * tasaCambio;

        resultadoTexto.innerHTML = 
            `${monto} ${monedaOrigen} = ${resultado.toFixed(2)} ${monedaDestino}`;

    } catch (error) {
        resultadoTexto.innerHTML = "Error al conectar con la API";
        console.log(error);
    }
}