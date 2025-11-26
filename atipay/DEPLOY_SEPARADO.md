# Guía: Desplegar Backend y Frontend en Repositorios Separados

Esta guía explica cómo dividir el proyecto actual en dos repositorios independientes (Backend en Laravel + Frontend en React) y desplegarlos en servidores separados, manteniendo toda la funcionalidad.

---

## 1. Estructura Final

```
Repositorio Backend: tu-repo-backend
├── app/
├── config/
├── database/
├── routes/
├── ...
└── (todo lo de la carpeta Backend/)

Repositorio Frontend: tu-repo-frontend
├── src/
├── index.html
├── package.json
├── .env.example
├── .env.local
├── vite.config.ts
└── (todo lo de la carpeta Frontend/)
```

---

## 2. Preparar Backend para Repositorio Separado

### 2.1 CORS - Permitir solicitudes desde el Frontend

Edita `Backend/config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => [
    'http://localhost:5173',                    // Desarrollo local
    'http://localhost:3000',                    // Si usas otro puerto
    'https://tudominio.com',                    // Producción
    'https://app.tudominio.com',                // Subdominio producción
],
'allowed_origins_patterns' => ['*.tudominio.com'], // Opcional: todos los subdominios
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true, // Importante para cookies/auth
```

### 2.2 Configurar JWT en `Backend/config/jwt.php`

Asegúrate de que `secret` esté configurado:

```bash
# En Backend/, generar clave JWT si no existe
php artisan jwt:secret
```

### 2.3 Variables de entorno del Backend (`Backend/.env`)

```dotenv
APP_URL=https://api.tudominio.com  # O tu host de backend en producción
API_URL=${APP_URL}/api

JWT_SECRET=tu-clave-secreta-jwt    # Generada por jwt:secret
JWT_ALGORITHM=HS256
JWT_TTL=60                         # Minutos; duración del token
```

### 2.4 Crear Repositorio Backend

```bash
cd Backend/
git init
git add .
git commit -m "Initial commit: Backend en Laravel"
git remote add origin https://github.com/tu-usuario/tu-repo-backend.git
git branch -M main
git push -u origin main
```

---

## 3. Preparar Frontend para Repositorio Separado

### 3.1 Variables de Entorno

El frontend ya está configurado para leer `VITE_API_URL`:

**Archivo: `Frontend/.env.example`**
```dotenv
VITE_API_URL=http://127.0.0.1:8000/api
```

**Archivo: `Frontend/.env.local`** (local, NO se versiona)
```dotenv
VITE_API_URL=http://127.0.0.1:8000/api
```

### 3.2 Configurar .gitignore en Frontend

Asegúrate de que `.gitignore` incluya:

```gitignore
.env.local
.env.*.local
node_modules/
dist/
```

### 3.3 Crear Repositorio Frontend

```bash
cd Frontend/
git init
git add .
git commit -m "Initial commit: Frontend en React + Vite"
git remote add origin https://github.com/tu-usuario/tu-repo-frontend.git
git branch -M main
git push -u origin main
```

---

## 4. Desplegar Backend en Producción (Ejemplo)

### Opción A: Vercel, Railway, Render (simples)

1. Conecta tu repositorio Backend.
2. Configura variables de entorno:
   - `APP_URL=https://api-tudominio.com`
   - `DB_CONNECTION=mysql` (o tu BD)
   - `JWT_SECRET=tu-clave`
3. Deploy automático en cada push.

### Opción B: Servidor Linux Tradicional

```bash
# En tu servidor
git clone https://github.com/tu-usuario/tu-repo-backend.git
cd tu-repo-backend
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Configurar Nginx/Apache como proxy a `php artisan serve` o usar php-fpm
```

---

## 5. Desplegar Frontend en Producción (Ejemplo)

### Opción A: Vercel, Netlify (recomendado para React)

1. Conecta tu repositorio Frontend.
2. Configurar Build:
   - Build command: `npm run build`
   - Output directory: `dist`
3. Variables de entorno:
   - `VITE_API_URL=https://api-tudominio.com/api`
4. Deploy automático en cada push.

### Opción B: GitHub Pages

```bash
# En Frontend/
npm run build
# Sube carpeta 'dist/' a GitHub Pages
```

### Opción C: Servidor Linux

```bash
cd tu-repo-frontend
npm install
npm run build
# Sirve 'dist/' con Nginx/Apache como contenido estático

# Ejemplo Nginx:
# server {
#   listen 80;
#   server_name app.tudominio.com;
#   root /var/www/dist;
#   index index.html;
#   try_files $uri $uri/ /index.html;
# }
```

---

## 6. Flujo de Desarrollo Local

### Terminal 1: Backend

```bash
cd Backend/
php artisan serve
# Corre en http://127.0.0.1:8000
```

### Terminal 2: Frontend

```bash
cd Frontend/
# Asegúrate de que .env.local tiene:
# VITE_API_URL=http://127.0.0.1:8000/api

npm run dev
# Corre en http://localhost:5173
```

### Pruebas

- Admin: http://localhost:5173/admin
- Usuario: http://localhost:5173/
- API: http://127.0.0.1:8000/api/admin/qualification/settings

---

## 7. Configuración de CORS en Diferentes Escenarios

### Desarrollo Local
```php
'allowed_origins' => ['http://localhost:5173'],
```

### Staging
```php
'allowed_origins' => ['https://app-staging.tudominio.com'],
```

### Producción
```php
'allowed_origins' => [
    'https://app.tudominio.com',
],
'allowed_origins_patterns' => ['*.tudominio.com'],
```

---

## 8. Consideraciones de Seguridad

- ✅ HTTPS en producción (ambos Frontend y Backend).
- ✅ CORS configurado restrictivamente (solo dominios permitidos).
- ✅ JWT con TTL corto (30-60 minutos) + refresh token (opcional).
- ✅ Rate limiting en API (Backend).
- ✅ Validación de entrada (Backend).
- ✅ CSRF tokens si usas cookies (ya cubierto por CORS + JWT).

---

## 9. Actualizar API URL en Runtime (Avanzado)

Si necesitas cambiar `VITE_API_URL` después del build:

```javascript
// En Frontend/src/main.tsx o App.tsx
const apiUrl = window.__API_URL__ || import.meta.env.VITE_API_URL;
```

Y en el HTML (`index.html`):

```html
<script>
  window.__API_URL__ = new URL(document.currentScript.src).searchParams.get('apiUrl') 
    || 'http://127.0.0.1:8000/api';
</script>
```

---

## 10. Testing Multi-Repositorio

```bash
# Backend en Terminal 1
cd Backend/ && php artisan serve

# Frontend en Terminal 2
cd Frontend/ && npm run dev

# Verifica en Browser:
# Admin: http://localhost:5173/admin
# Usuario: http://localhost:5173/
```

Abre Developer Tools → Network → verifica que las llamadas a `/api/...` apunten a `http://127.0.0.1:8000/api/...`.

---

## Resumen: Todo Funcionará Igual

- ✅ Sincronización multi-ventana (localStorage) — funciona igual.
- ✅ Calificación y retiros — funciona igual.
- ✅ Autenticación JWT — funciona igual.
- ✅ CORS — configurado correctamente.
- ✅ Variables de entorno — preparadas para cada escenario.

Con estos pasos, tendrás dos repositorios independientes que funcionan exactamente igual que antes.
