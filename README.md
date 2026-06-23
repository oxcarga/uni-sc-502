# SC-502: Ambiente Web - Cliente/Servidor

Repositorio de la materia **SC-502: Ambiente Web - Cliente/Servidor** de la carrera de Ingeniería en Computación.

---

## 📚 Descripción del Curso

Este repositorio contiene los trabajos y prácticas desarrollados a lo largo del curso, enfocados en la creación de aplicaciones web modernas con arquitectura cliente-servidor.

### Temas Principales
- Arquitectura web cliente-servidor
- HTML5 semántico y CSS3 avanzado
- Responsive Design y Mobile First
- Sistemas de diseño y componentes reutilizables
- Accesibilidad web (WCAG 2.1)
- Versionamiento con Git y GitHub
- **Backend con PHP y MySQL**
- **Containerización con Docker**
- Integración frontend-backend

---

## 🗂️ Estructura del Repositorio

```
.
├── README.md                           # Este archivo (descripción del curso)
├── proyecto/                           # Proyecto principal: Pulso Solidario
│   ├── README.md                       # Documentación completa del proyecto
│   ├── DESIGN.md                       # Sistema de diseño y tokens
│   ├── estaticos/                      # Maqueta frontend estática
│   ├── presentacion/                   # Presentación multimedia
│   └── src/                            # 🚀 RAÍZ DEL PROYECTO (Docker)
│       ├── docker-compose.yml          # Orquestación (PHP, MySQL, phpMyAdmin)
│       ├── Dockerfile                  # Configuración de contenedor PHP
│       ├── DOCKER.md                   # Documentación Docker
│       ├── QUICKSTART.md               # Guía de inicio rápido
│       ├── frontend/                   # HTML, CSS, JavaScript
│       ├── backend/                    # Código PHP
│       └── database/                   # Scripts SQL
├── practicas/                          # Ejercicios y prácticas de clase
│   ├── Práctica1/                      # Práctica1 - tarea
│   └── conversor/                      # Práctica2 - tarea  
└── .gitignore                          # Archivos ignorados por Git
```

---

## 📁 Contenido del Repositorio

### 🎯 [Proyecto Principal: Pulso Solidario](proyecto/)

Plataforma integral de gestión de donaciones de sangre con interfaz web responsiva y backend con PHP + MySQL.

**Stack Tecnológico**:
- Frontend: HTML5, CSS3, JavaScript, Bootstrap 5
- Backend: PHP 8.2
- Base de Datos: MySQL 8.0
- DevOps: Docker & Docker Compose

**Iniciar el proyecto**:
```bash
cd proyecto/src
docker-compose up -d
# Accede a http://localhost:8000
```

**Documentación**: [proyecto/README.md](proyecto/README.md)

### 📖 Prácticas de Clase
Ejercicios y materiales de aprendizaje desarrollados durante el curso.

**Ir a**: [practicas/README.md](practicas/README.md)

---

## 🚀 Quick Start - Pulso Solidario

### Requisitos
- Docker Desktop instalado ([Descargar](https://www.docker.com/products/docker-desktop))

### Iniciar la Aplicación
```bash
cd proyecto/src
docker-compose up -d
```

### Acceder a los Servicios
- 🌐 Frontend/Backend: http://localhost:8000
- 🗄️ phpMyAdmin: http://localhost:8080
- 🔌 MySQL: localhost:3306

Para más información, consulta [proyecto/src/QUICKSTART.md](proyecto/src/QUICKSTART.md)

---

## 📝 Objetivos del Curso

Este curso tiene como objetivo:

1. Comprender la arquitectura cliente-servidor en aplicaciones web modernas
2. Desarrollar habilidades en HTML5, CSS3 y JavaScript
3. Implementar interfaces responsivas y accesibles
4. Aplicar principios de diseño UX/UI en proyectos reales
5. Practicar control de versiones y flujos de trabajo colaborativos
6. **Desarrollar backends con PHP y bases de datos MySQL**
7. **Containerizar aplicaciones con Docker**
8. Integrar frontend con backends y APIs

---

**Última actualización**: 23 de junio de 2026