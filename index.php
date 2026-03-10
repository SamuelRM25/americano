<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['student_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';

    if (!empty($code)) {
        $stmt = $pdo->prepare('SELECT s.*, g.name as grade_name 
                               FROM students s 
                               JOIN grades g ON s.grade_id = g.id 
                               WHERE s.code = ?');
        $stmt->execute([$code]);
        $student = $stmt->fetch();

        if ($student) {
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['student_name'] = $student['name'];
            $_SESSION['grade_id'] = $student['grade_id'];
            $_SESSION['grade_name'] = $student['grade_name'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Código de estudiante no válido.';
        }
    } else {
        $error = 'Por favor ingresa tu código.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ing Samuel Ramírez</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8',
                            500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e',
                        },
                    },
                    animation: {
                        'blob': 'blob 10s infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'shake': 'shake 0.5s cubic-bezier(.36,.07,.19,.97) both',
                        'fade-in-up': 'fadeInUp 0.8s ease-out forwards',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0) rotate(0deg)' },
                            '50%': { transform: 'translateY(-20px) rotate(5deg)' },
                        },
                        shake: {
                            '10%, 90%': { transform: 'translate3d(-1px, 0, 0)' },
                            '20%, 80%': { transform: 'translate3d(2px, 0, 0)' },
                            '30%, 50%, 70%': { transform: 'translate3d(-4px, 0, 0)' },
                            '40%, 60%': { transform: 'translate3d(4px, 0, 0)' },
                        },
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }

        // Biometric Login Logic
        document.getElementById('biometric-login').addEventListener('click', async () => {
            if (!window.PublicKeyCredential) {
                alert("Tu dispositivo no admite biometría.");
                return;
            }

            // Check if we have a saved credential ID to simulate valid WebAuthn flow
            const savedBioId = localStorage.getItem('bio_id');
            if (!savedBioId) {
                alert("Primero debes entrar con tu código y activar Face ID desde tu panel de control.");
                return;
            }

            try {
                const response = await fetch('webauthn_handler.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ credential_id: savedBioId })
                });

                const result = await response.json();
                if (result.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    alert("Error: " + result.error);
                }
            } catch (err) {
                console.error("Error en biometría:", err);
                alert("Error al intentar autenticar.");
            }
        });
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #020617;
        }

        .glass-container {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .input-premium {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-premium:focus {
            background: rgba(56, 189, 248, 0.05);
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
        }

        .bg-grid {
            background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6 overflow-hidden relative bg-grid">
    <!-- Ethereal Background -->
    <div
        class="absolute top-0 -left-20 w-96 h-96 bg-primary-600/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob">
    </div>
    <div
        class="absolute bottom-0 -right-20 w-96 h-96 bg-indigo-600/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob animation-delay-4000">
    </div>

    <div class="max-w-7xl w-full grid grid-cols-1 lg:grid-cols-2 gap-20 items-center relative z-10">
        <!-- Left: Hero Branding -->
        <div class="hidden lg:block space-y-10 animate-fade-in-up">
            <div
                class="inline-flex items-center space-x-3 px-5 py-2.5 bg-white/5 border border-white/10 rounded-full backdrop-blur-md">
                <span class="flex h-2.5 w-2.5">
                    <span
                        class="animate-ping absolute inline-flex h-2.5 w-2.5 rounded-full bg-primary-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-primary-500"></span>
                </span>
                <span class="text-slate-300 text-xs font-black uppercase tracking-widest italic">Portal Académico
                    2026</span>
            </div>

            <h1 class="text-8xl font-black text-white leading-none tracking-tighter">
                Diseña tu <br>
                <span
                    class="text-transparent bg-clip-text bg-gradient-to-r from-primary-400 via-primary-500 to-indigo-500">Propio
                    Éxito.</span>
            </h1>

            <p class="text-slate-400 text-xl max-w-lg leading-relaxed font-medium">
                La plataforma inteligente donde el conocimiento se encuentra con la tecnología para crear el futuro hoy.
            </p>

            <div class="flex items-center space-x-12">
                <div class="space-y-1">
                    <p class="text-3xl font-black text-white">100%</p>
                    <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Interactividad</p>
                </div>
                <div class="w-px h-12 bg-white/10"></div>
                <div class="space-y-1">
                    <p class="text-3xl font-black text-white">Cloud</p>
                    <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Sincronización</p>
                </div>
            </div>
        </div>

        <!-- Right: Auth Architecture -->
        <div class="flex justify-center lg:justify-end">
            <div class="max-w-md w-full glass-container p-12 rounded-[3.5rem] shadow-2xl relative animate-fade-in-up">
                <!-- Floating Academy Icon -->
                <div class="absolute -top-12 left-1/2 -translate-x-1/2">
                    <div
                        class="bg-gradient-to-br from-primary-500 to-indigo-600 w-24 h-24 rounded-3xl flex items-center justify-center shadow-2xl shadow-primary-500/40 animate-float border border-white/20">
                        <i data-lucide="graduation-cap" class="w-12 h-12 text-white"></i>
                    </div>
                </div>

                <div class="text-center mt-12 mb-10">
                    <h2 class="text-4xl font-black text-white tracking-tight">Acceso Alumno</h2>
                    <p class="text-slate-500 mt-2 font-medium tracking-wide italic">"Innovación en cada entrega"</p>
                </div>

                <?php if ($error): ?>
                    <div
                        class="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-6 py-4 rounded-2xl text-sm font-bold flex items-center mb-8 animate-shake">
                        <i data-lucide="shield-alert" class="w-5 h-5 mr-3"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-8">
                    <div class="space-y-3">
                        <label for="code"
                            class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Identificador
                            de Alumno</label>
                        <div class="relative group">
                            <div
                                class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none transition-colors duration-300 group-focus-within:text-primary-400">
                                <i data-lucide="fingerprint" class="h-5 w-5 text-slate-600"></i>
                            </div>
                            <input type="text" name="code" id="code" required
                                class="w-full pl-14 pr-6 py-5 input-premium rounded-3xl text-white placeholder-slate-700 focus:outline-none text-lg font-bold tracking-tight"
                                placeholder="Eje: AL-2026-X">
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-white text-slate-950 font-black py-6 rounded-3xl hover:-translate-y-1 hover:shadow-2xl hover:shadow-primary-500/20 transition-all duration-300 flex items-center justify-center space-x-3 text-lg group">
                        <span>INGRESAR AHORA</span>
                        <i data-lucide="zap"
                            class="w-5 h-5 text-primary-600 group-hover:scale-125 transition-transform"></i>
                    </button>
                </form>

                <div class="mt-10 pt-8 border-t border-white/5 text-center">
                    <p class="text-slate-600 text-[9px] font-black uppercase tracking-[0.3em]">
                        Desarrollado por Ing Samuel Ramírez Systems
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>