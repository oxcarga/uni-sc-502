const apiKey = "ad80d82dc57c146744380f04";

const fromSelect = document.getElementById("from");
const toSelect = document.getElementById("to");
const form = document.getElementById("currency-form");
const result = document.getElementById("result");

let chart; // para controlar la gráfica

// CARGAR MONEDAS

async function loadCurrencies() {
  try {
    const res = await fetch(`https://v6.exchangerate-api.com/v6/${apiKey}/latest/USD`);
    const data = await res.json();

    const currencies = Object.keys(data.conversion_rates);

    let options = "";

    currencies.forEach(currency => {
      options += `<option value="${currency}">${currency}</option>`;
    });

    fromSelect.innerHTML = options;
    toSelect.innerHTML = options;

    fromSelect.value = "USD";
    toSelect.value = "CRC";

  } catch (error) {
    console.error(error);
  }
}

// GENERAR DATOS SIMULADOS (últimos días)

function generateHistory(baseRate) {
  let data = [];
  let labels = [];

  for (let i = 6; i >= 0; i--) {
    let variation = (Math.random() - 0.5) * 5; // variación aleatoria
    data.push((baseRate + variation).toFixed(2));

    const date = new Date();
    date.setDate(date.getDate() - i);
    labels.push(date.toLocaleDateString());
  }

  return { data, labels };
}

// CREAR GRÁFICA

function drawChart(labels, data) {
  const ctx = document.getElementById("chart");

  // Limpiar gráfica anterior
  if (chart) {
    chart.destroy();
  }

  chart = new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [{
        label: "Tipo de cambio",
        data: data,
        borderColor: "green",
        backgroundColor: "rgba(0, 200, 0, 0.1)",
        fill: true,
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });
}

// CONVERTIR
form.addEventListener("submit", async (e) => {
  e.preventDefault();

  try {
    const amount = parseFloat(document.getElementById("amount").value);

    if (isNaN(amount) || amount <= 0) {
      result.textContent = "Ingrese un monto válido";
      return;
    }

    const from = fromSelect.value;
    const to = toSelect.value;

    const res = await fetch(`https://v6.exchangerate-api.com/v6/${apiKey}/latest/${from}`);
    const data = await res.json();

    const rate = data.conversion_rates[to];

    // FLUCTUACIÓN
    const key = `${from}_${to}`;
    const previousRate = localStorage.getItem(key);

    let fluctuationText = "";

    if (previousRate !== null) {
      const change = ((rate - previousRate) / previousRate) * 100;

      if (change >= 0) {
        fluctuationText = `📈 +${change.toFixed(2)}%`;
      } else {
        fluctuationText = `📉 ${change.toFixed(2)}%`;
      }
    }

    localStorage.setItem(key, rate);

  
    // RESULTADO

    const converted = (amount * rate).toFixed(2);

    result.textContent = `${amount} ${from} = ${converted} ${to} ${fluctuationText}`;

  
    // GENERAR GRÁFICA
  
    const history = generateHistory(rate);

    drawChart(history.labels, history.data);

  } catch (error) {
    console.error(error);
    result.textContent = "Error en la conversión";
  }
});

// Ejecutar
loadCurrencies();
``