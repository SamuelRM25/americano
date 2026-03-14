<?php
session_start();
require_once 'config/database.php';
require_once 'includes/themes.php';
require_once 'includes/student_sidebar.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}

$content_id = $_GET['id'] ?? null;
if (!$content_id) {
    header('Location: dashboard.php');
    exit;
}

// Fetch content details
$stmt = $pdo->prepare("SELECT ec.*, c.name as course_name 
                       FROM educational_content ec 
                       JOIN courses c ON ec.course_id = c.id
                       WHERE ec.id = ?");
$stmt->execute([$content_id]);
$content = $stmt->fetch();

if (!$content) {
    header('Location: dashboard.php');
    exit;
}

$student_name = $_SESSION['student_name'];
$grade_name = $_SESSION['grade_name'];
$theme = get_grade_theme($grade_name);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $content['title'] ?> - Colegio Americano</title>
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

        .wiki-content :where(h1, h2, h3) {
            font-weight: 900;
            letter-spacing: -0.025em;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #0f172a;
        }

        .wiki-content h1 { font-size: 2.25rem; }
        .wiki-content h2 { font-size: 1.875rem; }
        .wiki-content p {
            margin-bottom: 1.25rem;
            line-height: 1.75;
            color: #475569;
        }
    </style>
</head>

<body class="bg-slate-50 selection:bg-accent-500 selection:text-white">
    <div class="flex h-screen overflow-hidden">
        <!-- Shared Student Sidebar -->
        <?php render_student_sidebar('dashboard', $theme, $student_name); ?>

        <!-- Main Content -->
        <main class="flex-1 min-h-0 overflow-y-auto bg-slate-50 relative p-8 lg:p-12 custom-scrollbar scroll-smooth">
            <header class="mb-12 flex flex-col md:flex-row md:items-center md:justify-between gap-6 animate-fade-in">
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="w-14 h-14 bg-white rounded-2xl border border-slate-200 flex items-center justify-center hover:border-accent-500 hover:text-accent-500 transition-all shadow-sm group">
                        <i data-lucide="arrow-left" class="w-6 h-6 group-hover:-translate-x-1 transition-transform"></i>
                    </a>
                    <div>
                        <h2 class="text-xs font-black text-accent-600 uppercase tracking-[0.3em] mb-1 leading-none"><?= $content['course_name'] ?></h2>
                        <h1 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter italic uppercase leading-none"><?= $content['title'] ?></h1>
                    </div>
                </div>
                <div>
                     <span class="px-6 py-3 bg-accent-50 text-accent-600 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-accent-100 italic shadow-sm">
                        Material de Clase
                    </span>
                </div>
            </header>

            <div class="glass-card rounded-[3rem] p-8 lg:p-16 shadow-2xl shadow-slate-200/50 animate-slide-up relative z-10">
                <?php if ($content['type'] === 'wiki'): ?>
                    <div class="wiki-content prose prose-slate max-w-none">
                        <?= $content['content'] ?>
                    </div>
                <?php elseif ($content['type'] === 'presentation'): ?>
                    <div class="aspect-video bg-slate-100 rounded-[2rem] overflow-hidden shadow-inner border border-slate-200">
                        <!-- Using Google Docs Viewer as a generic way to show PPTX -->
                        <iframe 
                            src="https://docs.google.com/viewer?url=<?= urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/admin/{$content['file_path']}") ?>&embedded=true" 
                            width="100%" 
                            height="100%" 
                            style="border: none;">
                        </iframe>
                    </div>
                    <div class="mt-12 flex justify-center">
                        <a href="admin/<?= $content['file_path'] ?>" download class="bg-slate-950 text-white px-12 py-6 rounded-[2rem] font-black text-xs uppercase tracking-[0.2em] hover:bg-accent-600 transition-all shadow-xl shadow-slate-900/20 flex items-center group">
                            <i data-lucide="download" class="w-5 h-5 mr-3 group-hover:translate-y-1 transition-transform"></i> Descargar Presentación
                        </a>
                    </div>
                <?php elseif ($content['type'] === 'scorm'): ?>
                    <?php
                    // Path to search for index.html
                    $scorm_id = basename($content['file_path'], '.zip');
                    $index_file = "admin/uploads/scorm/{$scorm_id}/index.html";
                    ?>
                    <div class="aspect-video bg-white rounded-[2rem] overflow-hidden shadow-2xl border border-slate-200">
                        <iframe src="<?= $index_file ?>" width="100%" height="100%" style="border: none;"></iframe>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
    <?php include 'includes/footer_scripts.php'; ?>
</body>

</html>
