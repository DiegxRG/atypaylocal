<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class DebugLogin extends Command
{
    protected $signature = 'debug:login {username} {password}';
    protected $description = 'Debug login process for a specific user';

    public function handle()
    {
        $username = $this->argument('username');
        $password = $this->argument('password');

        $this->info("🔍 Debugging login para: {$username}");
        $this->info('---');

        // 1. Buscar el usuario
        $user = User::where('username', $username)->orWhere('email', $username)->first();

        if (!$user) {
            $this->error("❌ Usuario NO encontrado");
            return;
        }

        $this->info("✅ Usuario encontrado: {$user->username} ({$user->email})");
        $this->info("   ID: {$user->id}");
        $this->info("   Rol: " . ($user->role ? $user->role->name : 'Sin rol'));
        $this->info("   Activo: " . ($user->login_inactive ? 'NO' : 'SÍ'));
        $this->info("   Hash guardado: " . substr($user->password, 0, 20) . "...");

        // 2. Verificar contraseña
        $passwordMatch = Hash::check($password, $user->password);

        if (!$passwordMatch) {
            $this->error("❌ Contraseña INCORRECTA");
            $this->info("   Hash enviado: " . Hash::make($password));
            return;
        }

        $this->info("✅ Contraseña CORRECTA");

        // 3. Verificar inactividad
        if ($user->login_inactive) {
            $this->warn("⚠️  Usuario inactivo - login sería rechazado");
            return;
        }

        $this->info("✅ Todas las verificaciones pasaron - login DEBERÍA funcionar");
    }
}
