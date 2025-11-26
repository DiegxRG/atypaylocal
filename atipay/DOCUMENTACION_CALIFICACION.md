# 📊 Sistema de Calificación por Puntos Mensuales

## ¿Qué hace?
Controla si un usuario puede retirar comisiones basado en puntos mensuales. El admin configura un mínimo, y los usuarios necesitan alcanzarlo para desbloquear retiros.

---

## 🔧 Componentes Implementados

### Backend
- **`QualificationController.php`**
  - `checkMyStatus()` - Verifica si el usuario califica (GET `/user/qualification-status`)
  - `getSettings()` - Obtiene el mínimo configurado (GET `/admin/qualification/settings`)
  - `updateMinPoints()` - Actualiza el mínimo (POST `/admin/qualification/update`)

- **`app/Models/SystemSetting.php`** - Almacena `min_monthly_points` en BD
- **`app/Models/MonthlyUserPoint.php`** - Registra puntos mensuales por usuario
- **Middleware `IsAdmin.php`** - Protege rutas de admin

### Frontend
- **`QualificationSettings.tsx`** - Panel admin para cambiar puntos mínimos
- **`QualificationStatusCard.tsx`** - Muestra estado del usuario
- **`UserWithdrawals.tsx`** - Bloquea botón "Nuevo Retiro" si no califica
- **`CommissionsSettings.tsx`** - Integra el panel en admin

### API Wrapper
- **`src/services/api.ts`** - Cliente fetch con métodos `.get()` y `.post()`

---

## 📍 Rutas API

| Método | Ruta | Protección | Función |
|--------|------|-----------|---------|
| GET | `/user/qualification-status` | Usuario | Ver calificación personal |
| GET | `/admin/qualification/settings` | Admin | Obtener mínimo configurado |
| POST | `/admin/qualification/update` | Admin | Actualizar mínimo |

---

## 🗂️ Archivos Modificados/Creados

**Backend:**
- `app/Console/Commands/VerifyAdminRole.php` - Comando para verificar rol admin
- `app/Http/Controllers/Api/Qualification/QualificationController.php` - Lógica de calificación

**Frontend:**
- `src/components/admin/Settings/QualificationSettings.tsx` - Panel admin (con validación de token)
- `src/components/dashboard/QualificationStatusCard.tsx` - Tarjeta de estado
- `src/pages/usuario/UserWithdrawals.tsx` - Bloqueo de retiros
- `src/pages/admin/CommissionsSettings.tsx` - Integración
- `src/services/api.ts` - Cliente HTTP

---

## ✅ Flujo de Funcionamiento

1. **Admin cambia mínimo** → Se guarda en `system_settings`
2. **Usuario accede a Mis Retiros** → Llama a `/user/qualification-status`
3. **Sistema compara**: puntos_usuario >= min_requerido
4. **Resultado**: Botón verde (activo) o gris (deshabilitado)

---

## 🐛 Problema Multi-Ventana Solucionado

### ¿Por qué pasaba el error?
Cuando abres 2 ventanas (Admin + Usuario):
- Comparten el mismo `localStorage`
- Al cambiar de ventana varias veces, el **token JWT expira**
- El servidor rechaza peticiones con token expirado → Error 403

### ✅ Solución Implementada
`QualificationSettings.tsx` ahora detecta errores 403 y **recarga automáticamente la página** para renovar el token.

**Comportamiento:**
- Si error 403 → Muestra "Sesión expirada" y recarga en 2 segundos
- Después de recargar → El usuario se re-autentica automáticamente
- Ahora puede cambiar puntos ilimitadamente sin problemas

---

## 🚀 Cómo Usar

**Admin:**
1. Panel → Configurar Comisiones
2. Cambiar "Puntos Mínimos Requeridos"
3. Guardar Cambios
4. Cambio se aplica a todos los usuarios instantáneamente

**Usuario:**
1. Ir a "Mis Retiros"
2. Si puntos >= mínimo requerido → botón VERDE
3. Si puntos < mínimo requerido → botón GRIS (bloqueado)


