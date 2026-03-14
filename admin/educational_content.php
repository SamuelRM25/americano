<?php
session_start();
require_once '../config/database.php';
require_once 'includes/sidebar.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

// Handle Deletion
if (isset($_GET['delete_content'])) {
    $id = $_GET['delete_content'];
    try {
        // Fetch file path to delete files if any
        $stmt = $pdo->prepare('SELECT file_path, type FROM educational_content WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        if ($item) {
            if ($item['type'] === 'scorm' && !empty($item['file_path'])) {
                $dir = dirname($item['file_path']);
                if (is_dir($dir)) {
                    // Recursive delete directory would be better, but for now just clear entry
                    // exec("rm -rf " . escapeshellarg($dir)); // Dangerous but effective for cleanup
                }
            } elseif ($item['type'] === 'presentation' && !empty($item['file_path'])) {
                if (file_exists($item['file_path'])) {
                    unlink($item['file_path']);
                }
            }
        }

        $stmt = $pdo->prepare('DELETE FROM educational_content WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: educational_content.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        $error = "Error al eliminar: " . $e->getMessage();
    }
}

// Handle Content Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_content'])) {
    $title = $_POST['title'] ?? '';
    $type = $_POST['type'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $grade_id = $_POST['grade_id'] ?? '';
    $content = $_POST['content'] ?? null;
    $file_path = null;

    if (!empty($title) && !empty($type) && !empty($course_id) && !empty($grade_id)) {
        try {
            if ($type === 'presentation') {
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('pres_') . '.' . $ext;
                    $dest = 'uploads/presentations/' . $filename;
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                        $file_path = $dest;
                    } else {
                        throw new Exception("Error al mover el archivo de presentación.");
                    }
                } else {
                    throw new Exception("Por favor sube un archivo PPTX.");
                }
            } elseif ($type === 'scorm') {
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $zip_file = $_FILES['file']['tmp_name'];
                    $zip = new ZipArchive;
                    if ($zip->open($zip_file) === TRUE) {
                        $folder_name = uniqid('scorm_');
                        $extract_path = 'uploads/scorm/' . $folder_name;
                        mkdir($extract_path, 0777, true);
                        $zip->extractTo($extract_path);
                        $zip->close();
                        
                        // Look for index.html or similar
                        $index_file = 'index.html';
                        if (!file_exists($extract_path . '/' . $index_file)) {
                            // Search recursively for any html file if index not found at root
                            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extract_path));
                            foreach ($files as $file) {
                                if ($file->isFile() && $file->getExtension() === 'html') {
                                    $index_file = str_replace($extract_path . '/', '', $file->getPathname());
                                    break;
                                }
                            }
                        }
                        $file_path = $extract_path . '/' . $index_file;
                    } else {
                        throw new Exception("Error al abrir el paquete ZIP.");
                    }
                } else {
                    throw new Exception("Por favor sube un archivo SCORM (ZIP).");
                }
            }

            $stmt = $pdo->prepare('INSERT INTO educational_content (admin_id, course_id, grade_id, type, title, content, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$_SESSION['admin_id'], $course_id, $grade_id, $type, $title, $content, $file_path]);
            $success = "Contenido guardado correctamente.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Por favor completa todos los campos obligatorios.";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $success = "Contenido eliminado correctamente.";
}

// Fetch grades and courses
$grades = $pdo->query('SELECT * FROM grades')->fetchAll();
$courses = $pdo->prepare('SELECT * FROM courses WHERE admin_id = ?');
$courses->execute([$_SESSION['admin_id']]);
$courses = $courses->fetchAll();

// Fetch educational content
$stmt = $pdo->prepare('SELECT ec.*, c.name as course_name, g.name as grade_name 
                      FROM educational_content ec 
                      JOIN courses c ON ec.course_id = c.id 
                      JOIN grades g ON ec.grade_id = g.id 
                      WHERE ec.admin_id = ? 
                      ORDER BY ec.id DESC');
$stmt->execute([$_SESSION['admin_id']]);
$contents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contenido Educativo - Admin</title>
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
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-panel { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .sidebar-item-active { background: rgba(14, 165, 233, 0.1); border-left: 4px solid #0ea5e9; color: white; }
    </style>
</head>
<body class="bg-[#f8fafc] h-screen flex overflow-hidden">
    <?php render_admin_sidebar('educational_content'); ?>

    <main class="flex-1 overflow-y-auto bg-slate-50 p-8 lg:p-12">
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
            <div>
                <h2 class="text-xs font-black text-primary-600 uppercase tracking-[0.3em] mb-2 leading-none">Material Didáctico</h2>
                <h1 class="text-5xl font-black text-slate-950 tracking-tighter">Gestión de <span class="italic text-transparent bg-clip-text bg-gradient-to-r from-slate-950 to-primary-600">Contenido</span></h1>
            </div>
            <button onclick="toggleModal('add-content-modal')" class="bg-primary-600 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-primary-500 transition-all shadow-xl shadow-primary-500/20 active:scale-95 flex items-center">
                <i data-lucide="plus" class="w-5 h-5 mr-2"></i> Crear Contenido
            </button>
        </header>

        <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-6 py-4 rounded-3xl text-sm font-bold mb-8 flex items-center">
                <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-rose-50 border border-rose-100 text-rose-700 px-6 py-4 rounded-3xl text-sm font-bold mb-8 flex items-center font-bold">
                <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($contents as $c): ?>
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl transition-all group">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-12 h-12 <?= $c['type'] === 'scorm' ? 'bg-violet-50 text-violet-600' : ($c['type'] === 'presentation' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600') ?> rounded-2xl flex items-center justify-center">
                            <i data-lucide="<?= $c['type'] === 'scorm' ? 'box' : ($c['type'] === 'presentation' ? 'presentation' : 'file-text') ?>" class="w-6 h-6"></i>
                        </div>
                        <a href="?delete_content=<?= $c['id'] ?>" onclick="return confirm('¿Eliminar este contenido?')" class="text-slate-300 hover:text-rose-500 transition-colors">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                        </a>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 mb-2 truncate"><?= $c['title'] ?></h3>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-1 rounded-lg font-black uppercase tracking-widest"><?= $c['grade_name'] ?></span>
                        <span class="text-[10px] bg-primary-50 text-primary-600 px-2 py-1 rounded-lg font-black uppercase tracking-widest"><?= $c['course_name'] ?></span>
                    </div>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">
                        <?= $c['type'] === 'scorm' ? 'Paquete Animado' : ($c['type'] === 'presentation' ? 'Presentación PPTX' : 'Material Wiki') ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal -->
    <div id="add-content-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-2xl rounded-[3.5rem] p-12 shadow-2xl relative animate-slide-up max-h-[90vh] overflow-y-auto">
            <header class="flex justify-between items-center mb-10">
                <h3 class="text-4xl font-black text-slate-950 tracking-tighter italic">Nuevo <span class="text-primary-500">Material</span></h3>
                <button onclick="toggleModal('add-content-modal')" class="p-4 bg-slate-100 text-slate-400 rounded-full hover:bg-slate-900 hover:text-white transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </header>
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="add_content" value="1">
                
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Título del Contenido</label>
                    <input type="text" name="title" required class="w-full px-8 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl outline-none focus:border-primary-500 font-bold transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Grado</label>
                        <select name="grade_id" required class="w-full px-6 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold">
                            <?php foreach ($grades as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= $g['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Curso</label>
                        <select name="course_id" required class="w-full px-6 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold">
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tipo de Contenido</label>
                    <select name="type" id="type_selector" onchange="updateFields()" required class="w-full px-6 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold">
                        <option value="wiki">Wiki (Información Estática)</option>
                        <option value="presentation">Presentación (PPTX)</option>
                        <option value="scorm">Paquete Animado (SCORM/ZIP)</option>
                    </select>
                </div>

                <div id="wiki_field" class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Contenido Wiki (Copy/Paste)</label>
                    <textarea name="content" rows="6" class="w-full px-8 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl outline-none focus:border-primary-500 font-bold transition-all"></textarea>
                </div>

                <div id="file_field" class="space-y-2 hidden">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Subir Archivo (.pptx o .zip)</label>
                    <input type="file" name="file" class="w-full px-8 py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold">
                </div>

                <button type="submit" class="w-full bg-primary-600 text-white font-black py-6 rounded-2xl mt-4 hover:bg-primary-500 shadow-xl transition-all uppercase tracking-widest text-sm">
                    Publicar Contenido
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden');
        }
        function updateFields() {
            const type = document.getElementById('type_selector').value;
            const wikiField = document.getElementById('wiki_field');
            const fileField = document.getElementById('file_field');
            
            if (type === 'wiki') {
                wikiField.classList.remove('hidden');
                fileField.classList.add('hidden');
            } else {
                wikiField.classList.add('hidden');
                fileField.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
