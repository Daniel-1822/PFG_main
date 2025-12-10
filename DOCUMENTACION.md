# NoteEd - Sistema de Grabación y Transcripción de Clases

## TECNOLOGÍAS Y HERRAMIENTAS

### 1. Lenguajes de Programación
- **JavaScript (ES6+)**: Frontend React y backend Node.js
- **PHP 7.4+**: APIs REST y autenticación
- **Python 3.8+**: Procesamiento de audio (transcripción)
- **SQL**: Consultas a base de datos MySQL

### 2. Frameworks Principales

#### Backend
- **Node.js + Express.js**: Servidor para transcripción y resumen de audio
- **PHP nativo**: Endpoints REST para CRUD de transcripciones y autenticación

#### Frontend
- **React 18**: Interfaz de usuario con componentes funcionales
- **Create React App**: Herramienta de construcción y desarrollo

### 3. Librerías y Dependencias Principales

#### Frontend (package.json)
```json
{
  "react": "^18.x",
  "react-dom": "^18.x",
  "axios": "^1.x" // Peticiones HTTP
}
```

#### Backend Node.js (class-recorder-backend)
- **express**: Framework web
- **cors**: Control de origen cruzado
- **multer**: Carga de archivos (audio)
- **openai**: API para resúmenes con GPT-4o-mini
- **dotenv**: Gestión de variables de entorno
- **python**: Ejecución de scripts (Whisper)

#### Python
- **openai-whisper**: Transcripción de audio con IA
- **openai**: SDK para resúmenes automáticos

### 4. Sistema de Gestión de Base de Datos
- **MySQL 5.7+** (XAMPP)
- **Base de datos**: `login_db`
- **Conexión**: mysqli en PHP (prepared statements)

---

## ARQUITECTURA Y DISEÑO

### 6. Tipo de Arquitectura
**Arquitectura Cliente-Servidor con 3 capas:**
- **Capa Presentación**: React (frontend)
- **Capa Aplicación**: PHP (APIs) + Node.js (IA)
- **Capa Datos**: MySQL

**Patrón MVC implícito:**
- **Models**: Tablas MySQL (users, transcriptions)
- **Views**: Componentes React
- **Controllers**: Endpoints PHP

### 7. Estructura de Carpetas del Proyecto

```
TFG/
├── class-recorder/                 # Frontend React
│   ├── public/
│   │   ├── index.html
│   │   ├── manifest.json
│   │   └── favicon.ico
│   ├── src/
│   │   ├── App.js                  # Componente principal (alumno + docente)
│   │   ├── App.css                 # Estilos globales
│   │   ├── index.js
│   │   └── reportWebVitals.js
│   ├── build/                      # Producción compilada
│   └── package.json
│
├── class-recorder-backend/         # Backend Node.js
│   ├── server.js                   # Servidor Express
│   ├── transcribe.py               # Script Python para Whisper
│   ├── .env                        # Variables de entorno (OPENAI_API_KEY)
│   ├── venv/                       # Entorno virtual Python
│   ├── uploads/                    # Audio temporal
│   └── package.json
│
├── *.php                           # Endpoints (Apache/XAMPP)
│   ├── index.php                   # Landing page
│   ├── login.php                   # Autenticación
│   ├── register.php                # Registro de usuarios
│   ├── logout.php                  # Cerrar sesión
│   ├── get_user_role.php           # Obtener rol del usuario
│   ├── get_transcriptions.php       # Transcripciones del alumno
│   ├── get_students.php            # Lista de alumnos (docente)
│   ├── get_student_transcriptions.php # Transcripciones de alumno (docente)
│   ├── save_transcription.php       # Guardar transcripción
│   ├── rename_transcription.php     # Renombrar transcripción
│   ├── edit_student_transcription.php # Editar (docente)
│   └── delete_transcription.php     # Eliminar transcripción
│
└── C:\xampp\htdocs\tfg/           # Producción (Apache)
    ├── index.html                  # Build React
    ├── static/                     # Assets compilados
    └── *.php                       # Endpoints PHP

```

### 8. Componentes Principales

| Componente | Ubicación | Función |
|-----------|-----------|---------|
| App.js | React | Componente principal con dos vistas (alumno/docente) |
| App.css | React | Estilos oscuros minimalistas |
| server.js | Node.js | Transcripción (Whisper) y resumen (GPT-4o-mini) |
| transcribe.py | Python | Ejecución del modelo Whisper |
| *.php | Apache | Autenticación, CRUD, gestión de sesiones |
| index.php | Landing | Página de inicio antes de login |
| MySQL | XAMPP | Persistencia de usuarios y transcripciones |

### 9. Comunicación entre Módulos

```
[ALUMNO/NAVEGADOR]
         ↓
    [React App]
         ↓
    ┌────┴────────┬──────────────┐
    ↓             ↓              ↓
[PHP Endpoints] [Node.js:5000] [Session Cookies]
    ↓             ↓
 [MySQL]    [Whisper + GPT]
    ↓
[Respuesta JSON]
    ↓
[React actualiza UI]
```

**Flujo de Audio:**
1. Usuario graba → `MediaRecorder API`
2. Envía a `http://localhost:5000/transcribe` (POST multipart)
3. Node.js ejecuta `transcribe.py` (Whisper)
4. Respuesta: `{transcription: "texto"}`
5. Envía a `http://localhost:5000/summarize` (POST JSON)
6. GPT-4o-mini genera resumen
7. Guarda en `./save_transcription.php` (POST JSON)
8. PHP inserta en MySQL con `$_SESSION['user_id']`

---

## BASE DE DATOS

### 11. Listado de Tablas/Entidades

#### Tabla: `users`
```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  role ENUM('alumno', 'docente') DEFAULT 'alumno',
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Campos principales:**
- `id`: PK (identificador único)
- `username`: Nombre de usuario (único)
- `role`: Tipo de usuario ('alumno' o 'docente')
- `password`: Hash bcrypt

#### Tabla: `transcriptions`
```sql
CREATE TABLE transcriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255),
  transcript LONGTEXT NOT NULL,
  summary LONGTEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Campos principales:**
- `id`: PK
- `user_id`: FK a users.id
- `title`: Nombre de la transcripción (editable)
- `transcript`: Texto completo de la grabación
- `summary`: Resumen automático (GPT-4o-mini)
- `created_at`: Timestamp de creación

### 12. Relaciones entre Tablas

```
users (1) ──────→ (N) transcriptions
  id                 user_id (FK)
  
Tipo: ONE-TO-MANY
- Un usuario tiene muchas transcripciones
- Una transcripción pertenece a un usuario
- ON DELETE CASCADE: Si se elimina usuario, se eliminan transcripciones
```

### 13. Descripción del Modelo de Datos

**Modelo normalizado 3FN:**
- `users`: Entidad principal de autenticación y roles
- `transcriptions`: Registros de grabaciones con relación 1:N

**Migraciones automáticas:**
- PHP crea tabla `transcriptions` si no existe (línea en `save_transcription.php`)
- Agrega columna `title` si falta (migración en tiempo de ejecución)

---

## FUNCIONALIDADES IMPLEMENTADAS

### 14. Lista Completa de Funcionalidades

#### Autenticación y Autorización
- ✅ Registro de nuevos usuarios (alumno/docente)
- ✅ Login con validación de credenciales (bcrypt)
- ✅ Sesiones PHP con cookies (same-origin)
- ✅ Logout con destrucción de sesión
- ✅ Diferenciación de vistas por rol

#### Funcionalidades de ALUMNO
- ✅ **Grabación de Audio**: MediaRecorder API nativo del navegador
- ✅ **Transcripción**: Whisper (OpenAI) en tiempo real
- ✅ **Resumen Automático**: GPT-4o-mini
- ✅ **CRUD Transcripciones**:
  - CREATE: Guardar (POST `/save_transcription.php`)
  - READ: Listar (GET `/get_transcriptions.php`)
  - UPDATE: Renombrar (POST `/rename_transcription.php`)
  - DELETE: Eliminar (POST `/delete_transcription.php`)
- ✅ **Edición inline**: Cambiar título con Enter/Escape

#### Funcionalidades de DOCENTE
- ✅ **Ver lista de alumnos** (GET `/get_students.php`)
- ✅ **Seleccionar alumno** y ver sus transcripciones
- ✅ **Editar contenido de alumno**:
  - Editar título (click)
  - Editar transcripción (click → textarea)
  - Editar resumen (click → textarea)
- ✅ Cambios se guardan automáticamente (POST `/edit_student_transcription.php`)

#### Funcionalidades Generales
- ✅ Panel responsivo (2 columnas desktop, 1 columna mobile)
- ✅ Interfaz oscura con tema amarillo (#f7d94c)
- ✅ Reproducción de audio grabado
- ✅ Visualización de transcripción y resumen en tiempo real

### 15. Flujo Principal de la Aplicación

#### Para ALUMNO:
```
1. [Landing] → index.php
2. [Login] → login.php (validar credenciales)
3. [Redirección] → /tfg/index.html (React)
4. [Cargar rol] → get_user_role.php
5. [Cargar transcripciones] → get_transcriptions.php
6. [UI Alumno] → Mostrar formulario de grabación
7. [Grabar] → startRecording() (MediaRecorder)
8. [Detener] → stopRecording()
9. [Enviar a backend] → http://localhost:5000/transcribe
10. [Obtener texto] → setTranscription()
11. [Resumir] → http://localhost:5000/summarize
12. [Obtener resumen] → setSummary()
13. [Guardar] → save_transcription.php (POST JSON)
14. [Insertar en BD] → INSERT transcriptions (MySQL)
15. [Actualizar UI] → Nueva tarjeta en lista
16. [Editar título] → Doble click → Input → Enter → rename_transcription.php
17. [Eliminar] → Botón "Borrar" → Confirmación → delete_transcription.php
18. [Cerrar sesión] → Botón "Cerrar Sesión" → logout.php
```

#### Para DOCENTE:
```
1-6. [Igual al alumno hasta paso 6]
7. [UI Docente] → Mostrar lista de alumnos (get_students.php)
8. [Click alumno] → Cargar transcripciones (get_student_transcriptions.php)
9. [Ver transcripciones de alumno] → Mostrar tarjetas
10. [Click en título] → Editar título en input
11. [Click en transcripción] → Editar en textarea
12. [Click en resumen] → Editar en textarea
13. [onBlur] → POST edit_student_transcription.php
14. [UPDATE] → Cambio guardado en MySQL
15. [Actualizar UI] → Mostrar contenido actualizado
```

### 16. APIs y Servicios Externos Integrados

#### OpenAI API (Node.js)
```javascript
// Resumen con GPT-4o-mini
POST http://localhost:5000/summarize
Body: { transcription: "texto largo..." }
Response: { summary: "resumen..." }

// Configuración:
const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY
});
```

#### OpenAI Whisper (Python)
```bash
# Transcripción de audio
python transcribe.py /path/to/audio.wav
# Output: JSON con transcripción

# Modelo: base (77M, rápido, precisión media)
# Soporta: MP3, MP4, MPEG, MPGA, M4A, WAV, WEBM
```

#### MediaRecorder API (Navegador)
```javascript
// Grabación nativa del navegador
navigator.mediaDevices.getUserMedia({ audio: true })
new MediaRecorder(stream)
// Output: Blob WAV
```

---

## DETALLES TÉCNICOS IMPORTANTES

### Autenticación y Sesiones
- **Método**: Cookies PHP (`PHPSESSID`)
- **Persistencia**: Session storage del servidor
- **Seguridad**: `password_verify()` con bcrypt
- **Scope**: Same-origin (`http://localhost`)

### Configuración de Producción
- **Servidor Web**: Apache (XAMPP)
- **Documento Root**: `C:\xampp\htdocs\tfg\`
- **React Build**: Rutas relativas (`./` en package.json)
- **PHP Endpoints**: Accesibles desde `/tfg/*.php`
- **Node.js Backend**: `http://localhost:5000` (mismo servidor)

### CORS (Node.js)
```javascript
app.use(cors({ 
  origin: ['http://localhost:3000', 'http://localhost'] 
}));
```

### Gestión de Archivos
- **Audio Temporal**: `class-recorder-backend/uploads/` (eliminado tras procesar)
- **Build Producción**: `class-recorder/build/` → `C:\xampp\htdocs\tfg`
- **Assets**: Rutas relativas (favicon, manifest, JS, CSS)

### Variables de Entorno (.env Node.js)
```
OPENAI_API_KEY=sk-... (obtenida de OpenAI)
```

### Base de Datos - Credenciales
```
Host: localhost:3306
User: root
Password: (sin contraseña)
Database: login_db
Charset: utf8mb4
```

### Puertos
- **Apache**: 80 (http://localhost)
- **Node.js**: 5000 (http://localhost:5000)
- **MySQL**: 3306 (localhost:3306)

---

## FLUJO DE DESPLIEGUE

1. **Desarrollo**: 
   - React en `npm start` (localhost:3000)
   - Node.js en `npm start` (localhost:5000)
   - PHP en Apache (localhost)

2. **Producción**:
   - Build React: `npm run build`
   - Copiar a `C:\xampp\htdocs\tfg\`
   - Endpoints PHP automáticamente disponibles
   - Node.js ejecutándose en segundo plano

---

## TECNOLOGÍAS ELEGIDAS Y JUSTIFICACIÓN

| Tecnología | Razón |
|-----------|-------|
| React | UI reactiva, componentes reutilizables, curva de aprendizaje moderada |
| Node.js | Integración con OpenAI, manejo de archivos audio, async/await |
| PHP | Compatibilidad Apache/XAMPP, gestión de sesiones simple, endpoints rápidos |
| MySQL | BD relacional estable, perfecta para este caso de uso |
| Whisper | Transcripción offline-capable, precisión alta, múltiples idiomas |
| GPT-4o-mini | Resúmenes de calidad, económico, API estable |

