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

### Prerequisitos

- **Docker Desktop** instalado y corriendo ([Descargar](https://www.docker.com/products/docker-desktop))
- Docker Compose (incluido en Docker Desktop)

### Iniciar el Proyecto con Docker

La forma más rápida y recomendada es usar Docker Compose:

```bash
# Navega a la carpeta src/
cd proyecto/src/

# Inicia todos los servicios (backend PHP, MySQL, phpMyAdmin)
docker-compose up -d
```

Listo! Tu proyecto está corriendo en:
- 🌐 **Frontend/Backend**: http://localhost:8000
- 🗄️ **phpMyAdmin**: http://localhost:8080 (User: `pulso_user` / Pass: `pulso_password`)
- 🔌 **MySQL**: localhost:3306

### Para Detener los Servicios

```bash
cd proyecto/src/
docker-compose down
```

### Documentación Completa de Docker

Para guías detalladas, comandos útiles y troubleshooting, consulta:
- **[src/QUICKSTART.md](src/QUICKSTART.md)** - Inicio rápido
- **[src/DOCKER.md](src/DOCKER.md)** - Documentación completa

### Alternativa: Desarrollo Local sin Docker (Deprecated)

Si prefieres ejecutar sin Docker (requiere PHP y MySQL instalados localmente):

```bash
# Opción 1: Abrir directamente en el navegador
# Navega a proyecto/ y abre index.html

# Opción 2: Usar servidor HTTP local
python3 -m http.server 8000

# O con Node.js:
npx http-server .
```

**Nota**: Se recomienda usar Docker para asegurar consistencia en todos los entornos.

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
- **Dashboard**: KPIs principales (donantes activos, donaciones este mes, tasa de conversión)
- **Gestión de Usuarios**: Tabla de usuarios con filtros y acciones (activar/desactivar, cambiar rol)
- **Reportes**: Gráficos de donaciones por mes, distribución por tipo de sangre, efectividad por centro
- **Configuración**: Políticas de donación, intervalos mínimos entre donaciones, criterios de elegibilidad
- **Audit Log**: Registro de cambios en el sistema con timestamp y usuario responsable
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
│   ├── index.html                       # Hub de navegación (página de inicio)
│   ├── pages/
│   │   ├── donor.html                   # Panel del Donante
│   │   ├── admin.html                   # Panel de Administración General
│   │   └── bank.html                    # Administración de Banco de Sangre
│   ├── assets/
│   │   ├── css/                         # Estilos
│   │   ├── img/                         # Imágenes y SVGs
│   │   ├── js/                          # Scripts de interacción
│   │   └── fonts/                       # Tipografías
│   └── screenshots/                     # Capturas de pantalla
├── presentacion/
│   └── pulso-solidario_v2.pptx          # Presentación multimedia
└── src/                                 # 🚀 RAÍZ DEL PROYECTO (Docker)
    ├── docker-compose.yml               # Orquestación de servicios
    ├── Dockerfile                       # Configuración PHP
    ├── docker-entrypoint.sh             # Script de inicialización
    ├── .env.example                     # Variables de entorno (template)
    ├── .dockerignore                    # Archivos a ignorar en Docker
    ├── DOCKER.md                        # Documentación Docker completa
    ├── QUICKSTART.md                    # Guía de inicio rápido
    ├── frontend/                        # 🌐 HTML, CSS, JavaScript
    │   └── README.md                    # Instrucciones frontend
    ├── backend/                         # 🔧 Archivos PHP
    │   └── README.md                    # Instrucciones backend
    └── database/                        # 🗄️ Scripts SQL
        └── README.md                    # Instrucciones base de datos
```

**Importante**: El desarrollo del proyecto ocurre dentro de la carpeta `src/`, que es la raíz de la aplicación Docker.

---

## 🎨 Sistema de Diseño

El proyecto implementa un **Sistema de Diseño Completo** definido en [DESIGN.md](DESIGN.md).

### Paleta de Colores

| Color | Valor | Uso |
|-------|-------|-----|
| **Primario (Teal)** | `#00685f` | Elemento principal, botones de acción, headers |
| **Secundario (Navy)** | `#545f73` | Elementos secundarios, subtítulos, estructuras |
| **Terciario (Rosa)** | `#b90538` | Alertas críticas, acciones urgentes, "Pulso" |
| **Fondo** | `#f8f9ff` | Fondo general de la interfaz |
| **Superficie** | `#ffffff` | Tarjetas, paneles, contenedores |
| **Error** | `#ba1a1a` | Validaciones negativas, errores |

### Tipografía

- **Titulares (Manrope)**: Moderna, equilibrada y confiable
  - Headline XL: 48px (desktop), 32px (mobile)
  - Headline LG: 32px (desktop), 24px (mobile)
  - Headline MD: 24px

- **Cuerpo (Inter)**: Legible, sistemática y óptima para datos
  - Body LG: 18px
  - Body MD: 16px (estándar)
  - Body SM: 14px
  - Label MD: 12px

### Componentes

#### Botones
- **Primarios**: Relleno sólido teal con texto blanco
- **Secundarios**: Contorno en teal con fondo transparente
- **Estados**: Normal, hover (oscurecimiento), activo, deshabilitado
- **Bordes redondeados**: `0.5rem` (lg)

#### Tarjetas (Cards)
- Fondo blanco con borde 1px en gris muy claro
- Para contenido destacado: efecto glasmorfismo con backdrop-blur
- Padding interno: 24px

#### Campos de Entrada
- Fondo en neutro-50 con borde 1px claro
- En focus: borde teal con glow suave
- Radio: `0.5rem`

#### Indicador de Pulso
- Componente único: punto animado con brillo en rosa terciario
- Indica datos en vivo o alertas urgentes
- Respeta `prefers-reduced-motion`

#### Listados
- Alta densidad sin bordes innecesarios
- Espaciado generoso vertical
- Zebra-striping sutil para legibilidad

### Responsive Design

- **Desktop-first**: Diseñado primero para pantallas grandes
- **Grid**: 12 columnas en desktop, 4 en mobile
- **Barra lateral**: Sidebar en desktop → Topbar horizontal en mobile
- **Breakpoints**: 
  - Desktop: >1024px (12 columnas)
  - Tablet: 768px-1024px (8 columnas)
  - Mobile: <768px (4 columnas)

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
- **Focus visible**: Indicadores claros de foco en todos los elementos interactivos

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

### DevOps & Herramientas
- **Docker & Docker Compose**: Containerización y orquestación de servicios
- **Git**: Control de versiones
- **GitHub**: Repositorio y colaboración
- **phpMyAdmin**: Interface gráfica para gestión de MySQL

### Navegadores soportados
- Chrome (últimas 2 versiones)
- Firefox (últimas 2 versiones)
- Safari (últimas 2 versiones)
- Edge (últimas 2 versiones)

---

## 📊 Métricas de Diseño

### Espaciado (basado en 8px)
- Base: 8px
- Gutter: 24px (espacios entre columnas)
- Margen mobile: 16px
- Margen desktop: 48px
- Ancho máximo del contenedor: 1280px

### Bordes Redondeados
- Small: 0.25rem (4px)
- Default: 0.5rem (8px)
- Medium: 0.75rem (12px)
- Large: 1rem (16px)
- Extra Large: 1.5rem (24px)
- Full (pill): 9999px

### Sombras y Elevación
- Sombras ambientes: Muy suave, opacidad 0.05-0.1
- Color de sombra: Ligeramente teñido con secundario navy
- Blur radius: 12px-20px para efecto glasmorfismo

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
  ├─ Solicita sangre → Queue de solicitudes
  ├─ Recibe asignación → Trazabilidad
  └─ Administra a paciente → Registro médico

Administrador
  ├─ Monitorea KPIs → Dashboard
  ├─ Gestiona usuarios → Base de datos
  ├─ Configura políticas → Sistema
  └─ Revisa reportes → Analytics
```

---

## 🔐 Consideraciones de Seguridad y Privacidad

*Nota: Esta es una maqueta frontend. Las siguientes consideraciones serán implementadas en la fase backend:*

- Encriptación de datos sensibles (información médica, grupo sanguíneo)
- Autenticación segura (OAuth 2.0 o JWT)
- Autorización basada en roles (RBAC)
- Auditoría completa de acciones
- Cumplimiento de HIPAA (Health Insurance Portability and Accountability Act)
- GDPR compliance para datos personales

---

## 📚 Referencias y Documentación

- [DESIGN.md](DESIGN.md) - Sistema de diseño completo con tokens y especificaciones
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.0/)
- [MDN Web Docs - Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [Material Design System](https://m3.material.io/)

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
- Dashboard analítico avanzado con BI

### Fase 4: Despliegue y Escalado
- Despliegue en producción usando Docker
- Orquestación (Kubernetes)
- CI/CD pipeline (GitHub Actions)
- Monitoreo y logging (ELK Stack)

---

## 👥 Información del Proyecto

**Proyecto**: Pulso Solidario
**Versión**: 1.0 (Maqueta Frontend)
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

**Última actualización**: 23 de junio de 2026
