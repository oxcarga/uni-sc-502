# Pulso Solidario — Plataforma de Gestión de Donaciones de Sangre

**Pulso Solidario** es una plataforma web integral para la gestión de donaciones de sangre que facilita la coordinación entre donantes, administradores de bancos de sangre y personal médico.

---

## 📋 Descripción del Proyecto

### Objetivo General
Crear una plataforma web moderna y accesible que simplifique la gestión de donaciones de sangre, mejorando la coordinación entre todos los actores del proceso (donantes, profesionales de salud, administradores) y garantizando la disponibilidad de sangre cuando más se necesita.

### Funcionalidades Principales

#### 🩸 Panel del Donante
Interfaz para donantes registrados que permite:
- Registrar perfil personal y antecedentes médicos
- Consultar historial de donaciones
- Agendar nuevas donaciones
- Monitorear su estado de elegibilidad según criterios médicos
- Recibir notificaciones sobre citas y alertas de disponibilidad crítica
- Acceder a reconocimientos por donaciones realizadas
- Consultar el impacto de sus donaciones

#### 👨‍💼 Panel de Administración General
Herramientas para administradores que incluyen:
- Gestión centralizada de usuarios (donantes, profesionales, administradores)
- Reportes y estadísticas de donaciones a nivel nacional/regional
- Monitoreo del estado del sistema en tiempo real
- Configuración de políticas y criterios de donación
- Gestión de centros de donación
- Auditoría de transacciones y cambios en el sistema

#### 🏥 Gestión de Banco de Sangre
Control del inventario de sangre:
- Monitoreo de existencias por tipo de sangre (O+, O-, A+, A-, B+, B-, AB+, AB-)
- Alertas automáticas de disponibilidad crítica
- Asignación de donaciones a centros médicos solicitantes
- Historial de movimientos de inventario
- Trazabilidad de unidades de sangre
- Reporte de compatibilidad de donantes

---

## 🚀 Cómo Empezar

Instrucciones de configuración, inicio con Docker y desarrollo local en **[src/README.md](src/README.md)**.

---

## 📱 Pantallas y Funcionalidades Detalladas

### 1. **Panel del Donante** (`pages/donor.html`)

**Secciones principales:**
- **Mi Perfil**: Información personal, grupo sanguíneo, antecedentes médicos
- **Historial de Donaciones**: Listado de todas las donaciones realizadas con fechas y ubicaciones
- **Agendar Donación**: Formulario para seleccionar fecha, hora y centro preferido
- **Elegibilidad**: Verificador interactivo que indica si el usuario puede donar según criterios actuales
- **Mi Impacto**: Visualización del impacto de sus donaciones (vidas salvadas, cantidad de unidades)
- **Logros**: Sistema de insignias por hitos de donación
- **Notificaciones**: Centro de alertas sobre citas próximas y alertas de disponibilidad

### 2. **Panel de Administración General** (`pages/admin.html`)

**Secciones principales:**
- **Panel principal**: Indicadores clave (donantes activos, donaciones este mes, tasa de conversión)
- **Gestión de Usuarios**: Tabla de usuarios con filtros y acciones (activar/desactivar, cambiar rol)
- **Reportes**: Gráficos de donaciones por mes, distribución por tipo de sangre, efectividad por centro
- **Configuración**: Políticas de donación, intervalos mínimos entre donaciones, criterios de elegibilidad
- **Registro de auditoría**: Registro de cambios en el sistema con marca de tiempo y usuario responsable
- **Bancos Registrados**: Gestión de centros de donación y coordinación

### 3. **Gestión de Banco de Sangre** (`pages/bank.html`)

**Secciones principales:**
- **Inventario en Vivo**: Visualización de existencias por tipo de sangre con indicadores de disponibilidad
  - Verde: Disponibilidad normal (>100 unidades)
  - Amarillo: Disponibilidad moderada (50-100 unidades)
  - Rojo: Disponibilidad crítica (<50 unidades)
- **Alertas Activas**: Notificaciones de donaciones requeridas urgentemente
- **Movimientos de Inventario**: Historial de recepciones y asignaciones
- **Solicitudes de Centros Médicos**: Cola de solicitudes pendientes
- **Compatibilidad de Donantes**: Búsqueda de donantes compatibles por tipo de sangre
- **Reporte de Trazabilidad**: Seguimiento completo de una unidad de sangre desde donación hasta administración

---

## 📁 Estructura de Archivos

```
proyecto/
├── README.md                            # Documentación del proyecto
├── DESIGN.md                            # Sistema de diseño completo
├── estaticos/                           # Archivos estáticos de la maqueta
│   ├── pages/                           # Vistas del donante, banco de sangre y admin
│   ├── assets/                          # Estilos, imágenes, scripts y tipografías
│   └── screenshots/                     # Capturas de pantalla
├── entregables/                         # .docx y y .pptx entregables del proyecto
└── src/                                 # 🚀 RAÍZ DEL PROYECTO (Docker)
```

**Importante**: El desarrollo del proyecto ocurre dentro de la carpeta `src/`, que es la raíz de la aplicación Docker.

---

## 🎨 Sistema de Diseño

El proyecto implementa un **Sistema de Diseño Completo** definido en [DESIGN.md](DESIGN.md).

### Paleta de Colores

| Color | Valor | Uso |
|-------|-------|-----|
| **Primario (Teal)** | `#00685f` | Elemento principal, botones de acción, encabezados |
| **Secundario (Navy)** | `#545f73` | Elementos secundarios, subtítulos, estructuras |
| **Terciario (Rosa)** | `#b90538` | Alertas críticas, acciones urgentes, "Pulso" |
| **Fondo** | `#f8f9ff` | Fondo general de la interfaz |
| **Superficie** | `#ffffff` | Tarjetas, paneles, contenedores |
| **Error** | `#ba1a1a` | Validaciones negativas, errores |

### Tipografía

- **Titulares (Manrope)**: Moderna, equilibrada y confiable
  - Titular XL: 48px (escritorio), 32px (móvil)
  - Titular LG: 32px (escritorio), 24px (móvil)
  - Titular MD: 24px

- **Cuerpo (Inter)**: Legible, sistemática y óptima para datos
  - Cuerpo LG: 18px
  - Cuerpo MD: 16px (estándar)
  - Cuerpo SM: 14px
  - Etiqueta MD: 12px

### Componentes

#### Botones
- **Primarios**: Relleno sólido teal con texto blanco
- **Secundarios**: Contorno en teal con fondo transparente
- **Estados**: Normal, al pasar el cursor (oscurecimiento), activo, deshabilitado
- **Bordes redondeados**: `0.5rem` (lg)

#### Tarjetas
- Fondo blanco con borde 1px en gris muy claro
- Para contenido destacado: efecto de glasmorfismo con desenfoque de fondo
- Relleno interno: 24px

#### Campos de Entrada
- Fondo en neutro-50 con borde 1px claro
- Al enfocar: borde teal con resplandor suave
- Radio: `0.5rem`

#### Indicador de Pulso
- Componente único: punto animado con brillo en rosa terciario
- Indica datos en vivo o alertas urgentes
- Respeta `prefers-reduced-motion`

#### Listados
- Alta densidad sin bordes innecesarios
- Espaciado generoso vertical
- Rayado alternado sutil para legibilidad

### Diseño responsivo

- **Escritorio primero**: Diseñado primero para pantallas grandes
- **Rejilla**: 12 columnas en escritorio, 4 en móvil
- **Barra lateral**: Barra lateral en escritorio → Barra superior horizontal en móvil
- **Puntos de quiebre**: 
  - Escritorio: >1024px (12 columnas)
  - Tableta: 768px-1024px (8 columnas)
  - Móvil: <768px (4 columnas)

---

## ♿ Accesibilidad (WCAG 2.1)

El proyecto implementa estándares de accesibilidad web:

- **Landmarks semánticos**: `<header>`, `<nav>`, `<main>`, `<aside>`, `<footer>`
- **Atributos ARIA**: 
  - `aria-current="page"` en navegación activa
  - `aria-label` en botones sin texto (iconos)
  - `aria-hidden` en SVGs decorativos
- **Imágenes**: Atributo `alt` descriptivo en todas
- **Contraste**: Cumple WCAG AA (4.5:1 para texto principal)
- **Animaciones**: Respeta `prefers-reduced-motion` para indicador de pulso
- **Navegación por teclado**: Todos los elementos son navegables con Tab/Shift+Tab
- **Foco visible**: Indicadores claros de foco en todos los elementos interactivos

---

## 🛠️ Tecnologías Utilizadas

### Frontend
- **HTML5**: Semántico y bien estructurado
- **CSS3**: Variables personalizadas (CSS Custom Properties) para tema
- **Bootstrap 5.3**: Framework responsive cargado desde CDN
- **Google Fonts**: Tipografías Manrope (titulares) e Inter (cuerpo)

### Backend (Desarrollando)
- **PHP 8.2**: Lenguaje backend (corriendo en Docker)
- **Apache 2.4**: Servidor web (corriendo en Docker)
- **MySQL 8.0**: Base de datos (corriendo en Docker)

### DevOps y herramientas
- **Docker y Docker Compose**: Containerización y orquestación de servicios
- **Git**: Control de versiones
- **GitHub**: Repositorio y colaboración
- **phpMyAdmin**: Interfaz gráfica para gestión de MySQL

### Navegadores soportados
- Chrome (últimas 2 versiones)
- Firefox (últimas 2 versiones)
- Safari (últimas 2 versiones)
- Edge (últimas 2 versiones)

---

## 📊 Métricas de Diseño

### Espaciado (basado en 8px)
- Base: 8px
- Canal entre columnas: 24px
- Margen móvil: 16px
- Margen escritorio: 48px
- Ancho máximo del contenedor: 1280px

### Bordes redondeados
- Pequeño: 0.25rem (4px)
- Predeterminado: 0.5rem (8px)
- Mediano: 0.75rem (12px)
- Grande: 1rem (16px)
- Extra grande: 1.5rem (24px)
- Completo (píldora): 9999px

### Sombras y Elevación
- Sombras ambientes: Muy suave, opacidad 0.05-0.1
- Color de sombra: Ligeramente teñido con secundario navy
- Radio de desenfoque: 12px-20px para efecto de glasmorfismo

---

## 🎯 Casos de Uso Principales

### Caso 1: Nuevo Donante se Registra
1. Donante accede a Panel del Donante
2. Completa perfil y antecedentes médicos
3. Sistema verifica elegibilidad según criterios
4. Se muestra tabla de disponibilidad de citas
5. Donante agenda primera donación
6. Admin recibe notificación de nuevo usuario
7. Centro de sangre prepara para donación

### Caso 2: Alerta de Disponibilidad Crítica
1. Banco de sangre detecta inventario bajo de O-
2. Sistema envía alerta al admin
3. Admin visualiza donantes compatibles en el panel
4. Contacta donantes frecuentes
5. Donantes reciben notificación de urgencia
6. Agendan donación acelerada
7. Inventario se repone

### Caso 3: Solicitud de Centro Médico
1. Hospital solicita 5 unidades de B+
2. Solicitud llega a Cola de Banco de Sangre
3. Banco verifica disponibilidad
4. Asigna unidades al hospital
5. Registra movimiento en historial
6. Actualiza inventario en tiempo real
7. Hospital recibe notificación de disponibilidad

---

## 🔄 Flujo de Datos

```
Donante
  ├─ Crea cuenta → Base de datos
  ├─ Ingresa información médica → Validación de elegibilidad
  ├─ Agenda cita → Sistema de reservas
  └─ Realiza donación → Registro en historial

Centro de Sangre
  ├─ Recibe donación → Procesamiento
  ├─ Almacena sangre → Inventario
  ├─ Monitorea stock → Alertas
  └─ Asigna a hospitales → Trazabilidad

Hospital
  ├─ Solicita sangre → Cola de solicitudes
  ├─ Recibe asignación → Trazabilidad
  └─ Administra a paciente → Registro médico

Administrador
  ├─ Monitorea indicadores → Panel principal
  ├─ Gestiona usuarios → Base de datos
  ├─ Configura políticas → Sistema
  └─ Revisa reportes → Analítica
```

---

## 🔐 Consideraciones de Seguridad y Privacidad

*Nota: Esta es una maqueta frontend. Las siguientes consideraciones serán implementadas en la fase backend:*

- Encriptación de datos sensibles (información médica, grupo sanguíneo)
- Autenticación segura (OAuth 2.0 o JWT)
- Autorización basada en roles (RBAC)
- Auditoría completa de acciones
- Cumplimiento de HIPAA (Health Insurance Portability and Accountability Act)
- Cumplimiento del GDPR para datos personales

---

## 📚 Referencias y Documentación

- [DESIGN.md](DESIGN.md) - Sistema de diseño completo con tokens y especificaciones
- [Documentación de Bootstrap 5](https://getbootstrap.com/docs/5.0/)
- [MDN Web Docs — Accesibilidad](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [Directrices WCAG 2.1](https://www.w3.org/WAI/WCAG21/quickref/)
- [Sistema Material Design](https://m3.material.io/)

---

## 🚧 Fases Futuras

### Fase 2: Backend y API
- Desarrollo de API REST con PHP (actualmente corriendo en Docker)
- Base de datos MySQL (actualmente corriendo en Docker)
- Autenticación y autorización
- Integración de notificaciones por email/SMS

### Fase 3: Funcionalidades Avanzadas
- Integración con sistemas hospitalarios (HL7/FHIR)
- App móvil nativa (React Native / Flutter)
- Mapas interactivos en tiempo real
- Panel analítico avanzado con inteligencia de negocios

### Fase 4: Despliegue y Escalado
- Despliegue en producción usando Docker
- Orquestación (Kubernetes)
- Pipeline de CI/CD (GitHub Actions)
- Monitoreo y registro (ELK Stack)

---

## 👥 Información del Proyecto

**Proyecto**: Pulso Solidario
**Versión**: 1.0 (Maqueta del frontend)
**Estudiante**: Oscar García Valerio
**Curso**: SC-502 - Ambiente Web - Cliente/Servidor
**Período**: 2026

---

## 📞 Contacto

Para preguntas sobre el proyecto:
- Email: oscar.garcia@[institución.edu]
- GitHub Issues: [reportar en el repositorio]

---

## 📄 Licencia

Este proyecto es parte de los trabajos académicos de la materia SC-502.
Todos los derechos reservados © 2026

---

**Última actualización**: 24 de junio de 2026
