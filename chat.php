<?php
session_start();
require_once 'config/database.php';
require_once 'includes/themes.php';
require_once 'includes/student_sidebar.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];
$grade_name = $_SESSION['grade_name'];
$theme = get_grade_theme($grade_name);

// Fetch teachers for this student's courses
$stmt_teachers = $pdo->prepare("SELECT DISTINCT a.id, a.username 
                                FROM admins a 
                                JOIN courses c ON a.id = c.admin_id 
                                JOIN student_courses sc ON c.id = sc.course_id 
                                WHERE sc.student_id = ?");
$stmt_teachers->execute([$student_id]);
$teachers = $stmt_teachers->fetchAll();

$selected_admin_id = $_GET['admin_id'] ?? ($teachers[0]['id'] ?? null);
$selected_admin_name = '';
foreach ($teachers as $t) {
    if ($t['id'] == $selected_admin_id) {
        $selected_admin_name = $t['username'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat con Soporte - Colegio Americano</title>
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
                        accent: {
                            50: 'var(--accent-50)', 100: 'var(--accent-100)', 200: 'var(--accent-200)',
                            300: 'var(--accent-300)', 400: 'var(--accent-400)', 500: 'var(--accent-500)',
                            600: 'var(--accent-600)', 700: 'var(--accent-700)', 800: 'var(--accent-800)', 900: 'var(--accent-900)',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out forwards',
                        'slide-up': 'slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(24px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <style>
        :root {
            --accent-50: #f0f9ff;
            --accent-500: #0ea5e9;
            --accent-600: #0284c7;
        }

        <?php if ($theme['accent'] === 'emerald'): ?>
            :root {
                --accent-50: #ecfdf5;
                --accent-500: #10b981;
                --accent-600: #059669;
            }

        <?php elseif ($theme['accent'] === 'amber'): ?>
            :root {
                --accent-50: #fffbeb;
                --accent-500: #f59e0b;
                --accent-600: #d97706;
            }

        <?php elseif ($theme['accent'] === 'rose'): ?>
            :root {
                --accent-50: #fff1f2;
                --accent-500: #f43f5e;
                --accent-600: #e11d48;
            }

        <?php elseif ($theme['accent'] === 'violet'): ?>
            :root {
                --accent-50: #f5f3ff;
                --accent-500: #8b5cf6;
                --accent-600: #7c3aed;
            }

        <?php endif; ?>

        body {
            font-family: 'Outfit', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .sidebar-active {
            background: rgba(255, 255, 255, 0.05);
            border-left: 4px solid var(--accent-500);
        }
    </style>
</head>

<body class="bg-slate-50 selection:bg-accent-500 selection:text-white overflow-hidden">
    <div class="flex h-screen overflow-hidden">
        <!-- Shared Student Sidebar -->
        <?php render_student_sidebar('chat', $theme, $student_name); ?>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-h-0 overflow-hidden bg-slate-50">
            <header
                class="p-8 lg:px-12 flex justify-between items-center bg-white border-b border-slate-100 shadow-sm z-10">
                <div>
                    <h2 class="text-xs font-black text-accent-600 uppercase tracking-widest mb-1">Catedráticos Enrolados
                    </h2>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <?php foreach ($teachers as $t): ?>
                            <a href="?admin_id=<?= $t['id'] ?>"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $selected_admin_id == $t['id'] ? 'bg-accent-600 text-white shadow-lg shadow-accent-500/20' : 'bg-slate-50 text-slate-500 hover:bg-slate-100' ?>">
                                <?= $t['username'] ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Chatting with</p>
                        <p class="text-sm font-black text-slate-900 leading-none">
                            <?= $selected_admin_name ?: 'Selecciona un profesor' ?></p>
                    </div>
                    <div
                        class="w-12 h-12 bg-accent-500 rounded-2xl flex items-center justify-center text-white font-black text-xl italic shadow-lg shadow-accent-500/20">
                        <?= substr($selected_admin_name ?: 'A', 0, 1) ?></div>
                </div>
            </header>

            <!-- Chat Area -->
            <div id="chat-messages" class="flex-1 overflow-y-auto p-8 lg:p-12 space-y-6 flex flex-col custom-scrollbar bg-slate-50/50">
                <!-- Messages will be injected here -->
            </div>

            <!-- Input Area -->
            <div class="p-8 lg:px-12 bg-white border-t border-slate-100">
                <form id="chat-form" class="flex gap-4">
                    <input type="text" id="message-input" placeholder="Escribe tu mensaje aquí..."
                        class="flex-1 px-8 py-4 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl outline-none focus:border-accent-500 font-bold transition-all text-slate-900">
                    <button type="submit"
                        class="bg-accent-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-accent-500 transition-all shadow-xl shadow-accent-500/20 active:scale-95 flex items-center">
                        <i data-lucide="send" class="w-5 h-5 mr-2"></i> Enviar
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        const chatContainer = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');

        const selectedAdminId = <?= json_encode($selected_admin_id) ?>;

        async function fetchMessages() {
            if (!selectedAdminId) return;
            try {
                const response = await fetch(`chat_handler.php?action=get_messages&admin_id=${selectedAdminId}`);
                const messages = await response.json();

                chatContainer.innerHTML = '';
                messages.forEach(msg => {
                    const isMe = msg.sender_type === 'student';
                    const div = document.createElement('div');
                    div.className = `flex ${isMe ? 'justify-end' : 'justify-start'} animate-fade-in`;

                    div.innerHTML = `
                        <div class="message-bubble p-6 rounded-[2rem] ${isMe ? 'bg-accent-600 text-white rounded-br-none' : 'bg-white border border-slate-100 text-slate-900 rounded-bl-none shadow-sm shadow-slate-200/50'}">
                            <p class="font-bold tracking-tight">${msg.message}</p>
                            <p class="text-[9px] mt-2 opacity-60 font-black uppercase">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</p>
                        </div>
                    `;
                    chatContainer.appendChild(div);
                });
                chatContainer.scrollTop = chatContainer.scrollHeight;
            } catch (err) {
                console.error('Error fetching messages:', err);
            }
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            messageInput.value = '';
            try {
                await fetch('chat_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message, admin_id: selectedAdminId })
                });
                fetchMessages();
            } catch (err) {
                console.error('Error sending message:', err);
            }
        });

        // Polling for new messages
        setInterval(fetchMessages, 3000);
        fetchMessages();
    </script>
    <?php include 'includes/footer_scripts.php'; ?>
</body>

</html>