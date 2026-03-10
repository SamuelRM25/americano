<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Filtering logic
$filter_grade = $_GET['grade_id'] ?? '';
$filter_course = $_GET['course_id'] ?? '';

// Fetch grades and teacher's specific courses for filters
$grades = $pdo->query('SELECT * FROM grades')->fetchAll();
$courses = $pdo->prepare('SELECT * FROM courses WHERE admin_id = ?');
$courses->execute([$_SESSION['admin_id']]);
$courses = $courses->fetchAll();

// Fetch all students associated with the current teacher's courses
$query = "SELECT DISTINCT s.id, s.name, s.code, g.name as grade_name,
    (SELECT message FROM chat_messages WHERE student_id = s.id AND admin_id = ? ORDER BY created_at DESC LIMIT 1) as last_message,
    (SELECT created_at FROM chat_messages WHERE student_id = s.id AND admin_id = ? ORDER BY created_at DESC LIMIT 1) as last_time,
    (SELECT COUNT(*) FROM chat_messages WHERE student_id = s.id AND admin_id = ? AND sender_type = 'student' AND is_read = 0) as unread_count
    FROM students s
    JOIN grades g ON s.grade_id = g.id
    JOIN student_courses sc ON s.id = sc.student_id
    JOIN courses c ON sc.course_id = c.id
    WHERE c.admin_id = ?";

$params = [$_SESSION['admin_id'], $_SESSION['admin_id'], $_SESSION['admin_id'], $_SESSION['admin_id']];

if ($filter_grade) {
    $query .= " AND s.grade_id = ?";
    $params[] = $filter_grade;
}
if ($filter_course) {
    $query .= " AND sc.course_id = ?";
    $params[] = $filter_course;
}

$query .= " ORDER BY last_time DESC, s.name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_student_id = $_GET['student_id'] ?? null;
$selected_student = null;

if ($selected_student_id) {
    $stmt = $pdo->prepare("SELECT s.*, g.name as grade_name FROM students s JOIN grades g ON s.grade_id = g.id WHERE s.id = ?");
    $stmt->execute([$selected_student_id]);
    $selected_student = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Mensajes - Admin</title>
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
                            50: '#f5f3ff', 100: '#ede9fe', 200: '#ddd6fe', 300: '#c4b5fd', 400: '#a78bfa',
                            500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 800: '#5b21b6', 900: '#4c1d95',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                        'slide-up': 'slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .sidebar-item-active {
            background: rgba(139, 92, 246, 0.1);
            border-left: 4px solid #8b5cf6;
            color: white;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        .chat-vh {
            height: calc(100vh - 160px);
        }
    </style>
</head>

<body class="bg-[#f8fafc] min-h-screen flex overflow-hidden">
    <!-- Admin Sidebar -->
    <aside class="w-80 bg-slate-950 text-white flex flex-col border-r border-white/5 shadow-2xl z-30 hidden lg:flex">
        <div class="p-8">
            <div class="flex items-center space-x-4 mb-12">
                <div class="bg-primary-500 p-3 rounded-2xl shadow-xl shadow-primary-500/20">
                    <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
                </div>
                <div>
                    <span class="text-xl font-black tracking-tighter block leading-none italic uppercase">ADMIN</span>
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest leading-none">Colegio
                        Americano</span>
                </div>
            </div>

            <nav class="space-y-2">
                <a href="dashboard.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                    <i data-lucide="users" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Estudiantes</span>
                </a>
                <a href="assignments.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                    <i data-lucide="book-open" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Asignar Tareas</span>
                </a>
                <a href="exams.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                    <i data-lucide="clipboard-list" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Asignar Exámenes</span>
                </a>
                <a href="chat.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all sidebar-item-active group">
                    <i data-lucide="message-square" class="w-5 h-5 text-accent-400"></i>
                    <span class="font-bold">Centro de Mensajes</span>
                </a>
                <a href="submissions.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all text-slate-400 hover:text-white hover:bg-white/5 group">
                    <i data-lucide="check-square" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Ver Calificaciones</span>
                </a>
            </nav>
        </div>

        <div class="mt-auto p-8 border-t border-white/5">
            <a href="logout.php"
                class="flex items-center space-x-3 text-slate-500 hover:text-rose-400 transition-colors group font-bold text-sm uppercase tracking-widest">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden bg-slate-50">
        <header class="p-8 lg:px-12 bg-white border-b border-slate-100 flex justify-between items-center z-10">
            <div>
                <h2 class="text-xs font-black text-accent-600 uppercase tracking-widest mb-1">Comunicación en Tiempo
                    Real</h2>
                <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Bandeja de <span
                        class="italic text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-accent-600">Entrada</span>
                </h1>
            </div>
            <?php if ($selected_student): ?>
                <div class="flex items-center space-x-4 animate-fade-in">
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">
                            <?= $selected_student['grade_name'] ?>
                        </p>
                        <p class="text-sm font-black text-slate-900 uppercase italic">
                            <?= $selected_student['name'] ?>
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-slate-900 text-white rounded-2xl flex items-center justify-center font-black italic shadow-lg">
                        <?= substr($selected_student['name'], 0, 1) ?>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <div class="flex flex-1 overflow-hidden">
            <!-- Student List -->
            <aside class="w-96 flex flex-col border-r border-slate-100 bg-white">
                <div class="p-6 border-b border-slate-50">
                    <div class="relative">
                        <i data-lucide="search"
                            class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300"></i>
                        <input type="text" placeholder="Buscar alumno..."
                            class="w-full pl-12 pr-6 py-3 bg-slate-50 border-2 border-transparent focus:border-accent-500 rounded-2xl outline-none font-bold text-xs transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-4">
                        <select id="grade_filter" onchange="applyFilters()"
                            class="px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-[10px] font-black uppercase outline-none focus:border-accent-500">
                            <option value="">Grados</option>
                            <?php foreach ($grades as $g): ?>
                                <option value="<?= $g['id'] ?>" <?= $filter_grade == $g['id'] ? 'selected' : '' ?>>
                                    <?= $g['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="course_filter" onchange="applyFilters()"
                            class="px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-[10px] font-black uppercase outline-none focus:border-accent-500">
                            <option value="">Cursos</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $filter_course == $c['id'] ? 'selected' : '' ?>>
                                    <?= $c['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <?php foreach ($students as $s): ?>
                        <a href="?student_id=<?= $s['id'] ?>"
                            class="flex items-center p-6 border-b border-slate-50 hover:bg-slate-50 transition-all group <?= $selected_student_id == $s['id'] ? 'bg-slate-50 border-l-4 border-l-accent-600' : '' ?>">
                            <div class="relative flex-shrink-0">
                                <div
                                    class="w-12 h-12 <?= $selected_student_id == $s['id'] ? 'bg-accent-600' : 'bg-slate-100' ?> rounded-2xl flex items-center justify-center font-black text-lg transition-colors <?= $selected_student_id == $s['id'] ? 'text-white' : 'text-slate-400 group-hover:text-accent-600' ?>">
                                    <?= substr($s['name'], 0, 1) ?>
                                </div>
                                <?php if ($s['unread_count'] > 0): ?>
                                    <span
                                        class="absolute -top-2 -right-2 w-6 h-6 bg-rose-500 text-white text-[10px] font-black rounded-full border-2 border-white flex items-center justify-center">
                                        <?= $s['unread_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="ml-4 flex-1 overflow-hidden">
                                <div class="flex justify-between items-center mb-1">
                                    <h4 class="text-sm font-black text-slate-950 truncate uppercase italic">
                                        <?= $s['name'] ?>
                                    </h4>
                                    <span class="text-[9px] text-slate-400 font-bold">
                                        <?= $s['last_time'] ? date('H:i', strtotime($s['last_time'])) : '' ?>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 font-medium truncate italic">
                                    <?= $s['last_message'] ?? 'Sin mensajes aún' ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <!-- Chat Window -->
            <section class="flex-1 flex flex-col bg-slate-50/50">
                <?php if ($selected_student): ?>
                    <div id="chat-messages" class="flex-1 overflow-y-auto p-10 space-y-6 flex flex-col custom-scrollbar">
                        <!-- Dynamic Messages -->
                    </div>
                    <!-- Input -->
                    <div class="p-8 bg-white border-t border-slate-100">
                        <form id="chat-form" class="flex gap-4">
                            <input type="text" id="message-input" placeholder="Escribe tu respuesta..."
                                class="flex-1 px-8 py-4 bg-slate-50 border-2 border-transparent border-slate-100 rounded-2xl outline-none focus:border-accent-500 font-bold transition-all text-slate-900">
                            <button type="submit"
                                class="bg-slate-950 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-accent-600 transition-all shadow-xl active:scale-95 flex items-center">
                                <i data-lucide="send" class="w-5 h-5 mr-2"></i> Responder
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="flex-1 flex flex-col items-center justify-center opacity-20">
                        <i data-lucide="message-circle" class="w-32 h-32 mb-6"></i>
                        <h3 class="text-3xl font-black italic uppercase">Selecciona un chat</h3>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
        lucide.createIcons();
        const studentId = <?= $selected_student_id ?? 'null' ?>;
        function applyFilters() {
            const grade = document.getElementById('grade_filter').value;
            const course = document.getElementById('course_filter').value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('grade_id', grade);
            currentUrl.searchParams.set('course_id', course);
            window.location.href = currentUrl.toString();
        }
        const chatContainer = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');

        async function fetchMessages() {
            if (!studentId) return;
            try {
                const response = await fetch(`../chat_handler.php?action=get_messages&student_id=${studentId}`);
                const messages = await response.json();

                chatContainer.innerHTML = '';
                messages.forEach(msg => {
                    const isAdmin = msg.sender_type === 'admin';
                    const div = document.createElement('div');
                    div.className = `flex ${isAdmin ? 'justify-end' : 'justify-start'} animate-fade-in`;

                    div.innerHTML = `
                        <div class="max-w-[70%] p-6 rounded-[2rem] ${isAdmin ? 'bg-slate-900 text-white rounded-br-none shadow-xl' : 'bg-white border border-slate-200 text-slate-900 rounded-bl-none shadow-sm'}">
                            <p class="font-bold tracking-tight">${msg.message}</p>
                            <p class="text-[9px] mt-2 opacity-60 font-black uppercase text-right">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</p>
                        </div>
                    `;
                    chatContainer.appendChild(div);
                });
                chatContainer.scrollTop = chatContainer.scrollHeight;
            } catch (err) {
                console.error('Error fetching messages:', err);
            }
        }

        if (chatForm) {
            chatForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const message = messageInput.value.trim();
                if (!message) return;

                messageInput.value = '';
                try {
                    await fetch('../chat_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message, student_id: studentId })
                    });
                    fetchMessages();
                } catch (err) {
                    console.error('Error sending message:', err);
                }
            });
        }

        if (studentId) {
            setInterval(fetchMessages, 3000);
            fetchMessages();
        }
    </script>
</body>

</html>