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

> **Estado de implementación:** el MVP (auth, paneles donante/banco, inventario, solicitudes, políticas, auditoría, logros) está en las fases **P0–P9** de [plan.md](plan.md). Gobierno admin completo (KPIs en vivo, usuarios, escritura de centros), campana de notificaciones en todos los shells, elegibilidad/impacto, ciclo completo de solicitudes + trazabilidad y reportes están en el backlog **P10–P15**. No hay portal de hospital ni roles `nurse`/`doctor` (solo `donor`, `bank`, `admin`).

---

## 🚀 Cómo Empezar

### Prerequisitos

- **Docker Desktop** instalado y corriendo ([Descargar](https://www.docker.com/products/docker-desktop))
- Docker Compose (incluido en Docker Desktop)

### Antes de empezar

1. (Opcional) Copia y personaliza las variables de entorno desde la raíz del repositorio:
   ```bash
   cp .env.example .env
   ```

2. Los directorios se crean automáticamente. ¡Solo agrega tus archivos!

### Iniciar el Proyecto con Docker

La forma más rápida y recomendada es usar Docker Compose desde la raíz del repositorio:

```bash
docker-compose up -d
```

¡Listo! Tu proyecto está corriendo en:
- 🌐 **Frontend**: http://localhost:3000
- 🔧 **API**: http://localhost:3001/api/
- 📧 **Mailhog** (correos de prueba): http://localhost:8025
- 🗄️ **phpMyAdmin**: http://localhost:3002 (Usuario: `pulso_user` / Contraseña: `pulso_password`)
- 🔌 **MySQL**: localhost:3306

### Cuentas demo (seed local)

Contraseña para todos: `demo1234`

| Rol | Correo | Panel |
|-----|--------|-------|
| Donante (A+) | `donante@test.com` | `/dashboard/donor/` |
| Donante (O-) | `donante_o@test.com` | `/dashboard/donor/` |
| Banco | `banco@test.com` | `/dashboard/bank/` |
| Admin | `admin@test.com` | `/dashboard/admin/` |

### Para Detener los Servicios

```bash
docker-compose down
```

### Mapeo de URLs

| Ubicación del archivo | URL de acceso |
|-----------------------|---------------|
| `frontend/pages/` (Nginx) | http://localhost:3000 |
| `backend/public/index.php` | http://localhost:3001/api/ |

### Comandos de Docker frecuentes

| Comando | Propósito |
|---------|-----------|
| `docker-compose up -d` | Iniciar todos los servicios |
| `docker-compose down` | Detener todos los servicios |
| `docker-compose logs -f` | Ver registros en vivo |
| `docker-compose logs -f backend` | Ver registros del servidor backend PHP |
| `docker-compose logs -f db` | Ver registros de MySQL |
| `docker-compose exec backend bash` | Acceder a la shell del contenedor PHP |
| `docker-compose exec db mysql -u pulso_user -p pulso_solidario` | Acceder a la CLI de MySQL |
| `docker-compose ps` | Mostrar contenedores en ejecución |
| `docker-compose up -d --build` | Reconstruir e iniciar los servicios |

### Solución de problemas

**¿Los servicios no inician?**
```bash
docker-compose down -v
docker-compose up -d --build
```

**Verificar si los contenedores están en ejecución:**
```bash
docker-compose ps
```

**Ver registros detallados:**
```bash
docker-compose logs backend
docker-compose logs db
```

### Documentación Completa de Docker

Para guías detalladas de servicios, volúmenes y configuración avanzada, consulta **[DOCKER.md](DOCKER.md)**.

### Alternativa: Desarrollo local sin Docker (obsoleto)

Si prefieres ejecutar sin Docker (requiere PHP y MySQL instalados localmente):

```bash
# Servidor HTTP local sobre frontend/
python3 -m http.server 8080 --directory frontend

# O con Node.js:
npx http-server frontend
```

**Nota**: Se recomienda usar Docker para asegurar consistencia en todos los entornos.

---

## 📱 Pantallas y Funcionalidades Detalladas

### 1. **Panel de Administración General** 

**Secciones principales:**
- **Panel principal**: Indicadores clave (P10: KPIs en vivo; hoy mock en home)
- **Gestión de Usuarios**: Tabla con filtros y activar/desactivar / rol (P10; UI demo hasta entonces)
- **Reportes**: Donaciones por mes, tipos de sangre, volumen por centro (P15)
- **Configuración**: Políticas de donación e intervalos (✅ P8 — `/dashboard/admin/settings/`)
- **Registro de auditoría**: Cambios con marca de tiempo y usuario (✅ P8 — `/dashboard/admin/audit/`)
- **Bancos Registrados**: Listado live; create/edit/activar (P11)

### 2. **Panel del Donante**

**Secciones principales:**
- **Mi Perfil**: Información personal, grupo sanguíneo, antecedentes médicos (✅)
- **Historial de Donaciones**: Listado de donaciones con fechas y ubicaciones (✅)
- **Agendar Donación**: Fecha, hora y centro preferido (✅)
- **Elegibilidad**: Verificador según criterios/políticas (P13)
- **Mi Impacto**: Unidades / vidas estimadas (P13)
- **Logros**: Insignias por hitos de donación (✅ P9)
- **Notificaciones**: Campana in-app (✅ en home; P12 en todo el shell + más tipos)

### 3. **Gestión de Banco de Sangre** 

**Secciones principales:**
- **Inventario en Vivo**: Existencias por tipo con indicadores de disponibilidad (✅)
  - Verde: Disponibilidad normal (>100 unidades)
  - Amarillo: Disponibilidad moderada (50-100 unidades)
  - Rojo: Disponibilidad crítica (<50 unidades)
- **Alertas Activas**: Notificaciones de donaciones requeridas urgentemente (✅)
- **Movimientos de Inventario**: Historial de recepciones y asignaciones (✅)
- **Solicitudes de Centros Médicos**: Cola y asignación (✅ list/assign; P14 create + ciclo `in_transit`/`completed`)
- **Compatibilidad de Donantes**: Búsqueda por tipo de sangre (✅)
- **Reporte de Trazabilidad**: Unidad desde donación hasta asignación (P14)
- **Settings del centro**: Persistencia de datos del banco (P11)

---

## 📁 Estructura de Archivos

```
./
├── README.md               # Documentación del proyecto
├── plan.md                 # Fases de implementación (P0–P15)
├── DESIGN.md               # Sistema de diseño
├── DOCKER.md               # Documentación Docker
├── docker-compose.yml      # Orquestación de servicios ⭐ Ejecutar desde aquí
├── Dockerfile              # Imagen PHP + Apache
├── apache-api.conf         # Alias /api → backend/public
├── nginx-frontend.conf     # Nginx: estáticos + proxy /api/
├── docker-entrypoint.sh    # Arranque del backend
├── .env.example            # Plantilla de variables de entorno
├── frontend/               # Interfaz web (HTML/JS/CSS) → http://localhost:3000
├── backend/                # API REST (SlimPHP) → http://localhost:3001/api/
└── database/               # Scripts SQL e inicialización
```

**Importante**: La raíz del repositorio es la raíz de la aplicación Docker. Ejecuta `docker-compose` desde aquí.

---

## 🎨 Sistema de Diseño

El proyecto implementa un sistema de diseño completo. Consulta **[DESIGN.md](DESIGN.md)** para paleta, tipografía, componentes y responsividad.

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

## 📚 Referencias y Documentación

- [DESIGN.md](DESIGN.md) - Sistema de diseño: tokens, tipografía y componentes
- [DOCKER.md](DOCKER.md) - Entorno Docker: servicios, volúmenes y configuración avanzada
- [Documentación de Bootstrap 5](https://getbootstrap.com/docs/5.0/)
- [MDN Web Docs — Accesibilidad](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [Sistema Material Design](https://m3.material.io/)

---

**Última actualización**: 23 de junio de 2026
