# Guía de Despliegue del Sistema Escolar

Este documento explica cómo desplegar la aplicación completa y cómo utilizar la demo estática en GitHub Pages.

## 1. GitHub Pages (Demo Estática)
GitHub Pages solo soporta archivos estáticos (HTML, CSS, JS). No puede ejecutar código PHP ni conectarse a bases de datos MySQL de forma nativa.

He creado una carpeta `static-demo/` que contiene una versión "maqueta" del sistema para que puedas mostrar la nueva interfaz moderna en GitHub Pages.

**Pasos para activar la demo:**
1. Sube tu repositorio a GitHub.
2. Ve a **Settings** > **Pages**.
3. Selecciona la rama `main` y la carpeta `(root)`.
4. Una vez publicado, accede a `tu-usuario.github.io/tu-repo/static-demo/index.html`.

## 2. Despliegue de la Aplicación Completa (PHP + MySQL)
Para que el sistema funcione realmente (registro de alumnos, exámenes, tareas, etc.), necesitas un hosting que soporte PHP y MySQL.

### Opciones Recomendadas:
*   **Hosting Compartido (CPanel/Plesk):** Hostinger, Bluehost, SiteGround, etc.
*   **Servidores Gratuitos:** 000webhost, InfinityFree (para pruebas).
*   **Plataformas Cloud:** Railway.app, Render.com (usando Docker o PHP Runtime).

### Pasos de Instalación en Hosting:
1. **Subir Archivos:** Sube todo el contenido de la raíz al administrador de archivos de tu hosting (usualmente `public_html`).
2. **Crear Base de Datos:** Crea una base de datos MySQL desde el panel de control de tu hosting.
3. **Importar Schema:** Importa el archivo `sql/schema.sql` en phpMyAdmin.
4. **Configurar Conexión:** Edita el archivo `config/database.php` con las credenciales que te proporcionó el hosting (host, dbname, usuario y contraseña).
5. **Permisos de Archivos:** Asegúrate de que la carpeta `uploads/` tenga permisos de escritura (chmod 755 o 777).

## 🚀 Despliegue en Render.com (Recomendado)

Esta aplicación está configurada para desplegarse automáticamente en Render.com usando **Docker** y **Render Blueprints**.

### Pasos para el despliegue:

1.  **Sube el código a GitHub**: Asegúrate de que todos los archivos (incluyendo `Dockerfile` y `render.yaml`) estén en tu repositorio.
2.  **Conecta con Render**: 
    - Ve a tu dashboard en [Render.com](https://dashboard.render.com).
    - Haz clic en **"New"** > **"Blueprint"**.
3.  **Selecciona tu Repositorio**: Conecta tu cuenta de GitHub y selecciona este proyecto.
4.  **Confirmar Despliegue**: Render detectará automáticamente el archivo `render.yaml`. Revisa los recursos (Servicio Web y MySQL) y haz clic en **"Apply"**.

**¡Listo!** Render creará automáticamente:
- Una base de datos MySQL gestionada.
- Un servicio web Dockerizado corriendo PHP 8.2 y Apache.
- Conectará ambos automáticamente mediante variables de entorno.

> [!NOTE]
> La primera vez que se despliegue, deberás importar el esquema de la base de datos (`sql/schema.sql`) a la nueva instancia de MySQL de Render si deseas que tenga datos iniciales.

---

## 🐳 Despliegue Local con Docker

Si deseas probar el contenedor localmente:

```bash
# Construir la imagen
docker build -t student-portal .

# Correr el contenedor (ajusta las variables de entorno)
docker run -p 8080:80 \
  -e DB_HOST=tu_host \
  -e DB_NAME=tu_db \
  -e DB_USER=tu_user \
  -e DB_PASS=tu_pass \
  student-portal
```

## 3. Notas sobre la Nueva Interfaz
*   **Personalización:** La apariencia cambia automáticamente según el nombre del grado del alumno (ej. "Primero", "Segundo", "Bachillerato").
*   **Tecnologías:** Se utiliza Tailwind CSS via CDN para facilitar el desarrollo, Lucide Icons para la iconografía y Google Fonts (Outfit) para el estilo premium.
