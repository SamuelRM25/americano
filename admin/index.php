<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $error = 'Por favor completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Colegio Americano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8',
                            500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a',
                        },
                    },
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #020617;
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .bg-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 bg-pattern relative">
    <!-- Decorative Accents -->
    <div class="absolute top-1/4 -left-20 w-80 h-80 bg-slate-800 rounded-full blur-[120px] opacity-40"></div>
    <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-slate-700 rounded-full blur-[120px] opacity-30"></div>

    <div class="max-w-md w-full relative z-10 animate-in fade-in zoom-in duration-700">
        <div class="text-center mb-10">
            <div
                class="bg-white/10 w-24 h-24 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-white/20 shadow-2xl">
                <i data-lucide="shield-check" class="w-12 h-12 text-white"></i>
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight">Superusuario</h1>
            <p class="text-slate-400 mt-2 font-medium tracking-wide uppercase text-xs">Acceso administrativo restringido
            </p>
        </div>

        <div class="glass-card p-10 rounded-[2.5rem] shadow-3xl space-y-8">
            <?php if ($error): ?>
                <div
                    class="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-6 py-4 rounded-2xl text-sm font-bold flex items-center animate-shake">
                    <i data-lucide="alert-triangle" class="w-5 h-5 mr-3"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label
                        class="block text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Usuario</label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-500">
                            <i data-lucide="user" class="h-5 w-5"></i>
                        </div>
                        <input type="text" name="username" required
                            class="w-full pl-12 pr-5 py-5 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-primary-500 transition-all font-bold"
                            placeholder="Introduce tu usuario">
                    </div>
                </div>

                <div class="space-y-2">
                    <label
                        class="block text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Contraseña</label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-500">
                            <i data-lucide="lock" class="h-5 w-5"></i>
                        </div>
                        <input type="password" name="password" required
                            class="w-full pl-12 pr-5 py-5 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-primary-500 transition-all font-bold"
                            placeholder="••••••••">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-white text-slate-950 font-black py-5 rounded-2xl hover:bg-slate-200 transition-all shadow-xl active:scale-[0.98] uppercase tracking-widest text-sm">
                    Autenticar Acceso
                </button>

                <button type="button" id="biometric-login"
                    class="w-full bg-slate-800/50 text-white font-black py-5 rounded-2xl hover:bg-slate-700 transition-all border border-white/10 active:scale-[0.98] uppercase tracking-widest text-xs flex items-center justify-center">
                    <i data-lucide="fingerprint" class="w-5 h-5 mr-3"></i> Acceder con Face ID
                </button>
            </form>
        </div>

        <p class="text-center text-slate-600 text-[10px] font-black uppercase tracking-[0.3em] mt-10">
            © 2026 Colegio Americano • Panel de Control
        </p>
    </div>

    <script>
        lucide.createIcons();

        document.getElementById('biometric-login').addEventListener('click', async () => {
            if (!window.PublicKeyCredential) {
                alert("Tu dispositivo no admite biometría.");
                return;
            }

            const savedBioId = localStorage.getItem('admin_bio_id');
            if (!savedBioId) {
                alert("Primero debes entrar con tu usuario y activar Face ID desde tu panel.");
                return;
            }

            try {
                const response = await fetch('../webauthn_handler.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ credential_id: savedBioId, type: 'admin' })
                });

                const result = await response.json();
                if (result.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    alert(result.error || "Error en la autenticación biométrica.");
                }
            } catch (err) {
                console.error(err);
                alert("Error al conectar con el servidor.");
            }
        });
    </script>
</body>

</html>