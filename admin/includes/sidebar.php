<?php
function render_admin_sidebar($active_page) {
    ?>
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
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'students' ? 'sidebar-item-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="users" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="<?= $active_page === 'students' ? 'font-bold' : 'font-medium' ?>">Estudiantes</span>
                </a>
                <a href="assignments.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'assignments' ? 'sidebar-item-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="book-open" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="<?= $active_page === 'assignments' ? 'font-bold' : 'font-medium' ?>">Asignar Tareas</span>
                </a>
                <a href="exams.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'exams' ? 'sidebar-item-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="clipboard-list" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="<?= $active_page === 'exams' ? 'font-bold' : 'font-medium' ?>">Asignar Exámenes</span>
                </a>
                <a href="chat.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'chat' ? 'sidebar-item-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="message-square" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="<?= $active_page === 'chat' ? 'font-bold' : 'font-medium' ?>">Centro de Mensajes</span>
                </a>
                <a href="submissions.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'submissions' ? 'sidebar-item-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="check-square" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="<?= $active_page === 'submissions' ? 'font-bold' : 'font-medium' ?>">Ver Calificaciones</span>
                </a>
                <a href="educational_content.php"
                    class="flex items-center space-x-3 p-4 rounded-2xl transition-all <?= $active_page === 'educational_content' ? 'sidebar-item-active' : 'text-slate-400 hover:text-white hover:bg-white/5 group' ?>">
                    <i data-lucide="library" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                    <span class="<?= $active_page === 'educational_content' ? 'font-bold' : 'font-medium' ?>">Contenido Educativo</span>
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

    <!-- Mobile Bottom Navigation (visible on small screens only) -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-slate-950 border-t border-white/5 z-50 flex items-center justify-around px-2 py-2">
        <a href="dashboard.php"
            class="flex flex-col items-center py-2 px-3 rounded-xl transition-all <?= $active_page === 'students' ? 'text-sky-400' : 'text-slate-500 hover:text-slate-300' ?>">
            <i data-lucide="users" class="w-5 h-5"></i>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1">Alumnos</span>
        </a>
        <a href="assignments.php"
            class="flex flex-col items-center py-2 px-3 rounded-xl transition-all <?= $active_page === 'assignments' ? 'text-sky-400' : 'text-slate-500 hover:text-slate-300' ?>">
            <i data-lucide="book-open" class="w-5 h-5"></i>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1">Tareas</span>
        </a>
        <a href="exams.php"
            class="flex flex-col items-center py-2 px-3 rounded-xl transition-all <?= $active_page === 'exams' ? 'text-sky-400' : 'text-slate-500 hover:text-slate-300' ?>">
            <i data-lucide="clipboard-list" class="w-5 h-5"></i>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1">Exámenes</span>
        </a>
        <a href="chat.php"
            class="flex flex-col items-center py-2 px-3 rounded-xl transition-all <?= $active_page === 'chat' ? 'text-sky-400' : 'text-slate-500 hover:text-slate-300' ?>">
            <i data-lucide="message-square" class="w-5 h-5"></i>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1">Chat</span>
        </a>
        <a href="submissions.php"
            class="flex flex-col items-center py-2 px-3 rounded-xl transition-all <?= $active_page === 'submissions' ? 'text-sky-400' : 'text-slate-500 hover:text-slate-300' ?>">
            <i data-lucide="check-square" class="w-5 h-5"></i>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1">Notas</span>
        </a>
        <a href="educational_content.php"
            class="flex flex-col items-center py-2 px-3 rounded-xl transition-all <?= $active_page === 'educational_content' ? 'text-sky-400' : 'text-slate-500 hover:text-slate-300' ?>">
            <i data-lucide="library" class="w-5 h-5"></i>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1">Biblioteca</span>
        </a>
        <a href="logout.php"
            class="flex flex-col items-center py-2 px-3 rounded-xl transition-all text-slate-500 hover:text-rose-400">
            <i data-lucide="log-out" class="w-5 h-5"></i>
            <span class="text-[9px] font-black uppercase tracking-widest mt-1">Salir</span>
        </a>
    </nav>
    <?php
}
?>
