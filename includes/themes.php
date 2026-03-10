<?php
/**
 * Theme helper for grade-specific styling
 */

function get_grade_theme($grade_name) {
    $themes = [
        'primero' => [
            'primary' => 'bg-emerald-600',
            'secondary' => 'bg-emerald-50',
            'text' => 'text-emerald-700',
            'border' => 'border-emerald-100',
            'gradient' => 'from-emerald-900 via-emerald-700 to-emerald-900',
            'icon' => 'book',
            'accent' => 'emerald'
        ],
        'segundo' => [
            'primary' => 'bg-blue-600',
            'secondary' => 'bg-blue-50',
            'text' => 'text-blue-700',
            'border' => 'border-blue-100',
            'gradient' => 'from-blue-900 via-blue-700 to-blue-900',
            'icon' => 'laptop',
            'accent' => 'blue'
        ],
        'tercero' => [
            'primary' => 'bg-indigo-600',
            'secondary' => 'bg-indigo-50',
            'text' => 'text-indigo-700',
            'border' => 'border-indigo-100',
            'gradient' => 'from-indigo-900 via-indigo-700 to-indigo-900',
            'icon' => 'code',
            'accent' => 'indigo'
        ],
        'cuarto' => [
            'primary' => 'bg-amber-600',
            'secondary' => 'bg-amber-50',
            'text' => 'text-amber-700',
            'border' => 'border-amber-100',
            'gradient' => 'from-amber-900 via-amber-700 to-amber-900',
            'icon' => 'briefcase',
            'accent' => 'amber'
        ],
        'quinto' => [
            'primary' => 'bg-rose-600',
            'secondary' => 'bg-rose-50',
            'text' => 'text-rose-700',
            'border' => 'border-rose-100',
            'gradient' => 'from-rose-900 via-rose-700 to-rose-900',
            'icon' => 'database',
            'accent' => 'rose'
        ],
        'sexto' => [
            'primary' => 'bg-violet-600',
            'secondary' => 'bg-violet-50',
            'text' => 'text-violet-700',
            'border' => 'border-violet-100',
            'gradient' => 'from-violet-900 via-violet-700 to-violet-900',
            'icon' => 'award',
            'accent' => 'violet'
        ],
        'bachillerato' => [
            'primary' => 'bg-cyan-600',
            'secondary' => 'bg-cyan-50',
            'text' => 'text-cyan-700',
            'border' => 'border-cyan-100',
            'gradient' => 'from-cyan-900 via-cyan-700 to-cyan-900',
            'icon' => 'graduation-cap',
            'accent' => 'cyan'
        ]
    ];

    $grade_lower = mb_strtolower($grade_name);
    
    foreach ($themes as $key => $theme) {
        if (strpos($grade_lower, $key) !== false) {
            return $theme;
        }
    }

    // Default theme
    return [
        'primary' => 'bg-slate-800',
        'secondary' => 'bg-slate-50',
        'text' => 'text-slate-700',
        'border' => 'border-slate-100',
        'gradient' => 'from-slate-900 via-slate-700 to-slate-800',
        'icon' => 'graduation-cap',
        'accent' => 'slate'
    ];
}
