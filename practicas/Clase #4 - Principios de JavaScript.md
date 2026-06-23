# Clase #4: Principios de JavaScript

**Prof. Adrián Garro Sánchez**

---

## 1. Introducción a JavaScript

JavaScript es un lenguaje de programación que se utiliza principalmente en desarrollo web para agregar interactividad y dinamismo a las páginas web.

**Ejemplo Básico — Hola Mundo en JavaScript:**

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>Demostración de JavaScript</h1>
    <p id="demo">Este es un párrafo.</p>

    <script>
      document.getElementById("demo").innerHTML = "Hola Mundo!";
    </script>
  </body>
</html>
```

---

## 2. Funciones en JavaScript

Las funciones son bloques de código diseñados para realizar una tarea específica y pueden ser reutilizadas.

**Ejemplo — Función para Cambiar Texto:**

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>Función en JavaScript</h1>
    <p id="texto">Texto original.</p>
    <button type="button" onclick="cambiarTexto()">Cambiar Texto</button>

    <script>
      function cambiarTexto() {
        document.getElementById("texto").innerHTML = "Texto cambiado.";
      }
    </script>
  </body>
</html>
```

---

## 3. Entradas de Datos en JavaScript

JavaScript puede recoger datos del usuario a través de diferentes formas, como cuadros de texto, botones, etc.

**Ejemplo — Entrada de Datos:**

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>Entrada de Datos en JavaScript</h1>
    <input type="text" id="entrada" value="" />
    <button type="button" onclick="mostrarEntrada()">Mostrar Entrada</button>
    <p id="mostrar">Aquí se mostrará la entrada.</p>

    <script>
      function mostrarEntrada() {
        var dato = document.getElementById("entrada").value;
        document.getElementById("mostrar").innerHTML = dato;
      }
    </script>
  </body>
</html>
```

---

## 4. Salidas de Datos en JavaScript

Las salidas de datos pueden ser mostradas de varias maneras en JavaScript, como en un elemento HTML, en una alerta, o en la consola.

**Ejemplo — Salida de Datos:**

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>Salida de Datos en JavaScript</h1>
    <button type="button" onclick="mostrarAlerta()">Mostrar Alerta</button>

    <script>
      function mostrarAlerta() {
        alert("Esta es una alerta de JavaScript!");
      }
    </script>
  </body>
</html>
```

---

## 5. Condiciones y Validaciones en JavaScript

JavaScript permite realizar comprobaciones y validar condiciones utilizando declaraciones como `if`, `else`, etc.

**Ejemplo — Validación de Edad:**

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>Validación de Edad en JavaScript</h1>
    <input type="number" id="edad" value="" />
    <button type="button" onclick="validarEdad()">Validar Edad</button>
    <p id="resultado"></p>

    <script>
      function validarEdad() {
        var edad = document.getElementById("edad").value;
        if (edad >= 18) {
          document.getElementById("resultado").innerHTML =
            "Eres mayor de edad.";
        } else {
          document.getElementById("resultado").innerHTML =
            "No eres mayor de edad.";
        }
      }
    </script>
  </body>
</html>
```

---

## 6. Estructuras Básicas en JavaScript

Las estructuras básicas incluyen bucles y condicionales que permiten controlar el flujo del programa.

**Ejemplo — Bucle For:**

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>Bucle For en JavaScript</h1>
    <p id="bucle"></p>

    <script>
      var texto = "";
      for (var i = 0; i < 5; i++) {
        texto += "El número es " + i + "<br>";
      }
      document.getElementById("bucle").innerHTML = texto;
    </script>
  </body>
</html>
```

---

## 7. Eventos en JavaScript

Los eventos en JavaScript son acciones que pueden ser detectadas por el script, como clics, movimientos del mouse, teclas presionadas, etc.

**Ejemplo — Evento Click:**

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>Evento Click en JavaScript</h1>
    <button onclick="alert('Botón presionado!')">Presiona aquí</button>
  </body>
</html>
```

---

## 8. Objetos en JavaScript

Los objetos en JavaScript son colecciones de propiedades y métodos que representan entidades del mundo real.

**Ejemplo — Objeto Persona:**

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>Objeto en JavaScript</h1>
    <p id="objeto"></p>

    <script>
      var persona = {
        nombre: "Juan",
        apellido: "Pérez",
        edad: 30,
        nombreCompleto: function () {
          return this.nombre + " " + this.apellido;
        },
      };

      document.getElementById("objeto").innerHTML = persona.nombreCompleto();
    </script>
  </body>
</html>
```

---

## 9. Promesas y la Fetch API

Ejemplo para realizar una solicitud de red asíncrona con `fetch` y Promesas.

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>Solicitud Asíncrona con Fetch API y Promesas</h1>
    <button id="cargarDatos">Cargar Datos</button>
    <div id="resultado"></div>

    <script>
      document
        .getElementById("cargarDatos")
        .addEventListener("click", function () {
          fetch("https://jsonplaceholder.typicode.com/posts/1")
            .then((response) => response.json())
            .then(
              (data) =>
                (document.getElementById("resultado").innerHTML =
                  JSON.stringify(data)),
            )
            .catch((error) => console.error("Error:", error));
        });
    </script>
  </body>
</html>
```

---

## 10. Creación y Uso de Clases en JavaScript

Ejemplo de cómo definir y utilizar clases en JavaScript, una característica de ES6.

```html
<!DOCTYPE html>
<html>
  <body>
    <h1>Clases en JavaScript</h1>
    <div id="info"></div>

    <script>
      class Persona {
        constructor(nombre, edad) {
          this.nombre = nombre;
          this.edad = edad;
        }
        saludar() {
          return `Hola, mi nombre es ${this.nombre} y tengo ${this.edad} años.`;
        }
      }

      let persona1 = new Persona("Ana", 25);
      document.getElementById("info").innerHTML = persona1.saludar();
    </script>
  </body>
</html>
```

---

## 11. Asincronía en JavaScript

**Ejemplo — Asincronía con `setTimeout`:**

```javascript
console.log("Inicio");

setTimeout(() => {
  console.log("Procesamiento asíncrono");
}, 2000);

console.log("Fin");
```

---

## 12. Promesas

**Ejemplo — Uso de Promesas:**

```javascript
let promesa = new Promise((resolve, reject) => {
  let condicion = true;
  if (condicion) {
    resolve("Promesa resuelta");
  } else {
    reject("Promesa rechazada");
  }
});

promesa
  .then((resultado) => console.log(resultado))
  .catch((error) => console.log(error));
```

---

## 13. Async/Await

**Ejemplo — Async/Await:**

```javascript
async function obtenerDatos() {
  try {
    let respuesta = await fetch("https://api.ejemplo.com/datos");
    let datos = await respuesta.json();
    console.log(datos);
  } catch (error) {
    console.error(error);
  }
}

obtenerDatos();
```

---

## 14. For Loop Asíncrono con Await

```javascript
// Función que simula una espera
function esperar(segundos) {
  return new Promise((resolve) => setTimeout(resolve, segundos * 1000));
}

// Función asíncrona que utiliza un bucle for
async function bucleForAsincrono() {
  for (let i = 0; i < 5; i++) {
    console.log(`Inicio de la iteración ${i}`);
    await esperar(2); // Espera de 2 segundos
    console.log(`Fin de la iteración ${i}`);
  }
  console.log("Bucle completado");
}

bucleForAsincrono();
```
