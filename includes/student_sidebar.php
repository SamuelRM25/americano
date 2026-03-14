<?php
function render_student_sidebar($active_page, $theme, $student_name) {
    ?>
    <aside
        class="w-72 bg-slate-950 text-white flex-shrink-0 hidden lg:flex flex-col border-r border-white/5 shadow-2xl z-30 transform transition-transform duration-500">
        <div class="p-8">
            <div class="flex items-center space-x-4 mb-12 animate-fade-in">
                <div class="bg-accent-500 p-3 rounded-2xl shadow-xl shadow-accent-500/20">
                    <i data-lucide="<?= $theme['icon'] ?>" class="w-8 h-8 text-white"></i>
                </div>
                <div>
                    <span
                        class="text-xl font-black tracking-tighter block leading-none italic uppercase">ALUMNO</span>
                    <span
                        class="text-[10px] text-slate-500 font-bold uppercase tracking-widest leading-none">Colegio
                        Americano</span>
                </div>
            </div>

            <nav class="space-y-2">
                <a href="dashboard.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'dashboard' ? 'sidebar-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 <?= $active_page === 'dashboard' ? 'text-accent-500' : 'group-hover:scale-110 transition-transform' ?>"></i>
                    <span class="<?= $active_page === 'dashboard' ? 'font-bold text-white' : 'font-medium' ?>">Inicio</span>
                </a>
                <a href="assignments.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'assignments' ? 'sidebar-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="book-open" class="w-5 h-5 <?= $active_page === 'assignments' ? 'text-accent-500' : 'group-hover:scale-110 transition-transform' ?>"></i>
                    <span class="<?= $active_page === 'assignments' ? 'font-bold text-white' : 'font-medium' ?>">Mis Tareas</span>
                </a>
                <a href="exams.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'exams' ? 'sidebar-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="clipboard-check" class="w-5 h-5 <?= $active_page === 'exams' ? 'text-accent-500' : 'group-hover:scale-110 transition-transform' ?>"></i>
                    <span class="<?= $active_page === 'exams' ? 'font-bold text-white' : 'font-medium' ?>">Exámenes</span>
                </a>
                <a href="calendar.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'calendar' ? 'sidebar-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="calendar" class="w-5 h-5 <?= $active_page === 'calendar' ? 'text-accent-500' : 'group-hover:scale-110 transition-transform' ?>"></i>
                    <span class="<?= $active_page === 'calendar' ? 'font-bold text-white' : 'font-medium' ?>">Mi Agenda</span>
                </a>
                <a href="chat.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'chat' ? 'sidebar-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="message-square" class="w-5 h-5 <?= $active_page === 'chat' ? 'text-accent-500' : 'group-hover:scale-110 transition-transform' ?>"></i>
                    <span class="<?= $active_page === 'chat' ? 'font-bold text-white' : 'font-medium' ?>">Chat</span>
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

    <!-- Mobile Navigation Bar -->
    <nav
        class="lg:hidden fixed bottom-0 inset-x-0 bg-slate-950 border-t border-white/5 px-8 py-4 flex items-center justify-between z-50">
        <a href="dashboard.php" class="p-4 <?= $active_page === 'dashboard' ? 'text-accent-500' : 'text-slate-400' ?>">
            <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
        </a>
        <a href="assignments.php" class="p-4 <?= $active_page === 'assignments' ? 'text-accent-500' : 'text-slate-400' ?>">
            <i data-lucide="book-open" class="w-6 h-6"></i>
        </a>
        <a href="chat.php" class="p-4 <?= $active_page === 'chat' ? 'text-accent-500' : 'text-slate-400' ?>">
            <i data-lucide="message-square" class="w-6 h-6"></i>
        </a>
        <a href="exams.php" class="p-4 <?= $active_page === 'exams' ? 'text-accent-500' : 'text-slate-400' ?>">
            <i data-lucide="clipboard-check" class="w-6 h-6"></i>
        </a>
        <a href="calendar.php" class="p-4 <?= $active_page === 'calendar' ? 'text-accent-500' : 'text-slate-400' ?>">
            <i data-lucide="calendar" class="w-6 h-6"></i>
        </a>
    </nav>
    <?php
}
?>
