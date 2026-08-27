<?php
/**
 * Central Game Assets & Dynamic Icons Manager for GhorbaniKids
 * Includes all standard and curriculum game SVG presets with vibrant gradients
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Game_Assets {

    /**
     * Predefined SVG presets with vibrant gradients for all games
     */
    public static function get_presets() {
        return [
            // --- General Skill Games (Originals) ---
            'birdhouse-memory' => [
                'title' => 'لانه پرندگان',
                'bg'    => 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M32 8L10 26h8v28h28V26h8L32 8z" fill="#ffffff" fill-opacity="0.95"/><circle cx="32" cy="36" r="8" fill="#0284c7"/><rect x="28" y="44" width="8" height="10" rx="4" fill="#fbbf24"/></svg>'
            ],
            'memory-card-match' => [
                'title' => 'تطبیق کارت‌ها',
                'bg'    => 'linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="10" y="14" width="26" height="38" rx="6" fill="#ffffff" fill-opacity="0.95"/><rect x="28" y="10" width="26" height="38" rx="6" fill="#fef08a"/><path d="M23 28l-4 8h8l-4-8z" fill="#8b5cf6"/><circle cx="41" cy="28" r="4" fill="#db2777"/></svg>'
            ],
            'picnic-basket-memory' => [
                'title' => 'سبد پیک‌نیک',
                'bg'    => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M12 28h40l-5 24H17L12 28z" fill="#ffffff" fill-opacity="0.95"/><path d="M32 10c-9 0-16 8-16 18h6c0-6 4-12 10-12s10 6 10 12h6c0-10-7-18-16-18z" fill="#fef08a"/></svg>'
            ],
            'rainbow-steps' => [
                'title' => 'ردپای رنگین‌کمان',
                'bg'    => 'linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M8 50C8 30 20 14 32 14s24 16 24 36" stroke="#ffffff" stroke-width="6" stroke-linecap="round"/><path d="M16 50C16 36 24 24 32 24s16 12 16 26" stroke="#fef08a" stroke-width="5" stroke-linecap="round"/><path d="M24 50c0-7 4-14 8-14s8 7 8 14" stroke="#38bdf8" stroke-width="4" stroke-linecap="round"/></svg>'
            ],
            'shadow-match' => [
                'title' => 'کارآگاه سایه‌ها',
                'bg'    => 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="28" cy="28" r="16" stroke="#38bdf8" stroke-width="5"/><path d="M40 40l14 14" stroke="#38bdf8" stroke-width="6" stroke-linecap="round"/><circle cx="28" cy="28" r="8" fill="#ffffff" fill-opacity="0.9"/></svg>'
            ],
            'tower-architect-3d' => [
                'title' => 'معمار برج ۳ بعدی',
                'bg'    => 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="14" y="42" width="36" height="14" rx="3" fill="#ffffff" fill-opacity="0.95"/><rect x="20" y="26" width="24" height="14" rx="3" fill="#fef08a"/><rect x="26" y="10" width="12" height="14" rx="3" fill="#ffffff"/></svg>'
            ],
            'tower-architect' => [
                'title' => 'معماری برج رویایی',
                'bg'    => 'linear-gradient(135deg, #6366f1 0%, #4338ca 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="12" y="32" width="40" height="22" rx="4" fill="#ffffff" fill-opacity="0.95"/><path d="M12 32l8-14h8l-4 14h-12zM40 32l-4-14h8l8 14H40z" fill="#fef08a"/><rect x="26" y="12" width="12" height="20" fill="#ffffff"/></svg>'
            ],
            'stroop-focus' => [
                'title' => 'موشک تمرکز (استروپ)',
                'bg'    => 'linear-gradient(135deg, #ef4444 0%, #b91c1c 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M32 6c-8 12-10 24-8 36l8-4 8 4c2-12 0-24-8-36z" fill="#ffffff" fill-opacity="0.95"/><circle cx="32" cy="24" r="5" fill="#ef4444"/><path d="M24 42l-8 10h8l4-6M40 42l8 10h-8l-4-6" fill="#fef08a"/></svg>'
            ],
            'paygah-tamarkoz' => [
                'title' => 'پایگاه تمرکز',
                'bg'    => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><ellipse cx="32" cy="34" rx="24" ry="10" fill="#ffffff" fill-opacity="0.95"/><ellipse cx="32" cy="28" rx="14" ry="12" fill="#fef08a"/><circle cx="24" cy="34" r="2.5" fill="#10b981"/><circle cx="32" cy="34" r="2.5" fill="#10b981"/><circle cx="40" cy="34" r="2.5" fill="#10b981"/></svg>'
            ],
            'neon-pathmaker' => [
                'title' => 'مسیرساز نئونی',
                'bg'    => 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M36 6L14 36h16l-4 22 24-32H32l4-20z" fill="#fef08a" stroke="#ffffff" stroke-width="2" stroke-linejoin="round"/></svg>'
            ],
            'magic-jewels' => [
                'title' => 'جواهرات جادویی',
                'bg'    => 'linear-gradient(135deg, #a855f7 0%, #7e22ce 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M20 14h24l12 16-24 24L8 30l12-16z" fill="#ffffff" fill-opacity="0.95"/><path d="M20 14l12 16 12-16M32 30v24M8 30h48" stroke="#a855f7" stroke-width="2.5"/></svg>'
            ],
            'jewelry-memory' => [
                'title' => 'جعبه جواهرات جادویی',
                'bg'    => 'linear-gradient(135deg, #eab308 0%, #ca8a04 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M10 22l8 24h28l8-24-12 8-10-14-10 14-12-8z" fill="#ffffff" fill-opacity="0.95"/><circle cx="32" cy="16" r="3" fill="#eab308"/><circle cx="12" cy="22" r="3" fill="#eab308"/><circle cx="52" cy="22" r="3" fill="#eab308"/></svg>'
            ],
            'chi-avaz-shod' => [
                'title' => 'چی عوض شد؟',
                'bg'    => 'linear-gradient(135deg, #14b8a6 0%, #0f766e 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="28" cy="28" r="16" stroke="#ffffff" stroke-width="5"/><path d="M40 40l14 14" stroke="#fef08a" stroke-width="6" stroke-linecap="round"/><path d="M22 28h12M28 22v12" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/></svg>'
            ],
            'bubble-detective' => [
                'title' => 'کارآگاه حباب',
                'bg'    => 'linear-gradient(135deg, #38bdf8 0%, #0284c7 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="26" cy="36" r="14" fill="#ffffff" fill-opacity="0.9"/><circle cx="42" cy="22" r="10" fill="#fef08a"/><circle cx="44" cy="44" r="7" fill="#ffffff" fill-opacity="0.8"/></svg>'
            ],
            'animal-memory' => [
                'title' => 'حافظه حیوانات',
                'bg'    => 'linear-gradient(135deg, #f97316 0%, #c2410c 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="32" cy="34" r="18" fill="#ffffff" fill-opacity="0.95"/><circle cx="18" cy="18" r="8" fill="#fef08a"/><circle cx="46" cy="18" r="8" fill="#fef08a"/><circle cx="26" cy="30" r="3" fill="#c2410c"/><circle cx="38" cy="30" r="3" fill="#c2410c"/><path d="M28 38c2 3 6 3 8 0" stroke="#c2410c" stroke-width="2.5" stroke-linecap="round"/></svg>'
            ],

            // --- 28 New Games (Skill & Curriculum) ---
            'balloon-path-3d' => [
                'title' => 'مسیر بادکنک‌ها ۳ بعدی',
                'bg'    => 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><ellipse cx="24" cy="24" rx="12" ry="15" fill="#f43f5e"/><ellipse cx="40" cy="20" rx="14" ry="17" fill="#ffffff" fill-opacity="0.95"/><ellipse cx="38" cy="18" rx="10" ry="13" fill="#fef08a"/><path d="M40 37l-2 18M24 39l4 16" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round"/><circle cx="44" cy="14" r="2.5" fill="#ffffff"/></svg>'
            ],
            'butterfly-flutter' => [
                'title' => 'پرواز پروانه‌ها',
                'bg'    => 'linear-gradient(135deg, #ec4899 0%, #db2777 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M32 20c-8-12-22-6-20 8s12 14 20 6c8 8 22 0 20-6s-12-20-20-8z" fill="#ffffff" fill-opacity="0.95"/><path d="M32 30c-6 4-14 12-8 18s12-2 8-18c-4 16 14 24 8 18s-2-14-8-18z" fill="#fef08a"/><ellipse cx="32" cy="28" rx="2.5" ry="14" fill="#831843"/><path d="M30 16c-4-6-8-4-8-2M34 16c4-6 8-4 8-2" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/></svg>'
            ],
            'birthday-candle-3d' => [
                'title' => 'شمع تولد ۳ بعدی',
                'bg'    => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="12" y="34" width="40" height="20" rx="6" fill="#ffffff" fill-opacity="0.95"/><path d="M12 38c6 4 14 4 20 0s14-4 20 0" stroke="#f43f5e" stroke-width="3" stroke-linecap="round"/><rect x="28" y="20" width="8" height="16" rx="3" fill="#38bdf8"/><path d="M32 8c-3 4-4 7 0 10 4-3 3-6 0-10z" fill="#ef4444"/><circle cx="32" cy="14" r="2" fill="#fef08a"/></svg>'
            ],
            'clock-hands-sequence' => [
                'title' => 'توالی عقربه‌های ساعت',
                'bg'    => 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="32" cy="32" r="22" fill="#ffffff" fill-opacity="0.95"/><circle cx="32" cy="32" r="18" stroke="#4f46e5" stroke-width="2"/><path d="M32 18v14l9 5" stroke="#ef4444" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="32" cy="32" r="3" fill="#4f46e5"/><circle cx="32" cy="12" r="2" fill="#fef08a"/></svg>'
            ],
            'coaster-windows' => [
                'title' => 'پنجره‌های ترن هوایی',
                'bg'    => 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M8 44c16-12 32-12 48 0" stroke="#ffffff" stroke-width="4" stroke-linecap="round"/><rect x="18" y="22" width="28" height="18" rx="5" fill="#ffffff" fill-opacity="0.95"/><rect x="22" y="26" width="9" height="9" rx="2" fill="#38bdf8"/><rect x="33" y="26" width="9" height="9" rx="2" fill="#fef08a"/><circle cx="24" cy="42" r="3.5" fill="#f59e0b"/><circle cx="40" cy="42" r="3.5" fill="#f59e0b"/></svg>'
            ],
            'symbol-color-pairs' => [
                'title' => 'جفت‌های رنگ و نماد',
                'bg'    => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="22" cy="24" r="12" fill="#ffffff" fill-opacity="0.95"/><polygon points="22,16 25,23 32,23 26,27 28,34 22,30 16,34 18,27 12,23 19,23" fill="#f59e0b"/><circle cx="42" cy="40" r="12" fill="#fef08a"/><polygon points="42,32 45,39 52,39 46,43 48,50 42,46 36,50 38,43 32,39 39,39" fill="#10b981"/></svg>'
            ],
            'fruit-bowl-memory' => [
                'title' => 'حافظه ظرف میوه',
                'bg'    => 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M12 32c0 14 18 22 20 22s20-8 20-22H12z" fill="#ffffff" fill-opacity="0.95"/><circle cx="24" cy="24" r="10" fill="#ef4444"/><circle cx="38" cy="22" r="9" fill="#facc15"/><circle cx="44" cy="28" r="6" fill="#84cc16"/><path d="M24 14c-1-4 3-6 5-4" stroke="#15803d" stroke-width="2.5" stroke-linecap="round"/></svg>'
            ],
            'emotion-recall-3d' => [
                'title' => 'تشخیص احساسات ۳ بعدی',
                'bg'    => 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="32" cy="32" r="22" fill="#ffffff" fill-opacity="0.95"/><circle cx="32" cy="32" r="19" fill="#fde047"/><circle cx="24" cy="26" r="3.5" fill="#1e293b"/><circle cx="40" cy="26" r="3.5" fill="#1e293b"/><path d="M22 36c3 6 17 6 20 0" stroke="#1e293b" stroke-width="3" stroke-linecap="round"/><ellipse cx="18" cy="34" rx="3" ry="2" fill="#f43f5e"/><ellipse cx="46" cy="34" rx="3" ry="2" fill="#f43f5e"/></svg>'
            ],
            'lollipop-match' => [
                'title' => 'آب‌نبات‌های جورچین',
                'bg'    => 'linear-gradient(135deg, #f43f5e 0%, #e11d48 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="32" cy="24" r="16" fill="#ffffff" fill-opacity="0.95"/><path d="M32 8a16 16 0 0 1 16 16c0 8-8 16-16 16s-10-6-10-10 6-6 10-6 4 2 4 4" stroke="#f43f5e" stroke-width="3.5" stroke-linecap="round"/><rect x="30" y="40" width="4" height="18" rx="2" fill="#ffffff"/><circle cx="42" cy="16" r="2" fill="#fef08a"/></svg>'
            ],
            'rainy-umbrellas' => [
                'title' => 'چترهای بارانی',
                'bg'    => 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M12 36c0-14 9-22 20-22s20 8 20 22c-5-3-10-3-14 0-4-3-8-3-12 0-4-3-9-3-14 0z" fill="#ffffff" fill-opacity="0.95"/><path d="M22 18c3 4 5 11 5 18M32 14v22M42 18c-3 4-5 11-5 18" stroke="#f43f5e" stroke-width="2.5"/><path d="M32 36v14c0 4-3 4-4 2" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/><circle cx="16" cy="46" r="2" fill="#93c5fd"/><circle cx="46" cy="44" r="2" fill="#93c5fd"/><circle cx="38" cy="52" r="2" fill="#93c5fd"/></svg>'
            ],
            'rhythm-studio' => [
                'title' => 'استودیو ریتم و آهنگ',
                'bg'    => 'linear-gradient(135deg, #a855f7 0%, #9333ea 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="20" cy="42" r="6" fill="#ffffff" fill-opacity="0.95"/><circle cx="44" cy="34" r="6" fill="#ffffff" fill-opacity="0.95"/><path d="M26 42V18l24-8v24" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M26 24l24-8" stroke="#fef08a" stroke-width="4"/><circle cx="35" cy="14" r="3" fill="#f43f5e"/></svg>'
            ],
            'sound-sequence-memory' => [
                'title' => 'حافظه توالی صداها',
                'bg'    => 'linear-gradient(135deg, #14b8a6 0%, #0d9488 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M12 26h8l12-10v32l-12-10h-8V26z" fill="#ffffff" fill-opacity="0.95"/><path d="M40 22c4 4 4 16 0 20M46 16c8 8 8 24 0 32" stroke="#fef08a" stroke-width="4" stroke-linecap="round"/><circle cx="50" cy="32" r="3" fill="#f43f5e"/></svg>'
            ],
            'stained-glass-pattern' => [
                'title' => 'شیشه‌های رنگی و الگو',
                'bg'    => 'linear-gradient(135deg, #d946ef 0%, #c026d3 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="14" y="14" width="36" height="36" rx="8" fill="#ffffff" fill-opacity="0.95"/><path d="M14 14l36 36M50 14L14 50M32 14v36M14 32h36" stroke="#c026d3" stroke-width="2.5"/><circle cx="32" cy="32" r="8" fill="#fef08a" stroke="#d946ef" stroke-width="2"/></svg>'
            ],
            'traffic-signs-matrix' => [
                'title' => 'ماتریس علائم راهنمایی',
                'bg'    => 'linear-gradient(135deg, #e11d48 0%, #be123c 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><polygon points="20,10 44,10 54,20 54,44 44,54 20,54 10,44 10,20" fill="#ffffff" fill-opacity="0.95"/><polygon points="22,14 42,14 50,22 50,42 42,50 22,50 14,42 14,22" fill="#be123c"/><rect x="22" y="28" width="20" height="8" rx="2" fill="#ffffff"/></svg>'
            ],
            'traffic-light-memo-3d' => [
                'title' => 'چراغ راهنمایی ۳ بعدی',
                'bg'    => 'linear-gradient(135deg, #334155 0%, #1e293b 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="22" y="8" width="20" height="48" rx="8" fill="#ffffff" fill-opacity="0.95"/><circle cx="32" cy="18" r="5.5" fill="#ef4444"/><circle cx="32" cy="32" r="5.5" fill="#eab308"/><circle cx="32" cy="46" r="5.5" fill="#22c55e"/></svg>'
            ],
            'harf-gomshode' => [
                'title' => 'حرف گمشده',
                'bg'    => 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="10" y="14" width="24" height="24" rx="6" fill="#ffffff" fill-opacity="0.95"/><text x="22" y="32" font-size="16" font-family="sans-serif" font-weight="bold" fill="#0284c7" text-anchor="middle">؟</text><circle cx="42" cy="38" r="14" stroke="#f59e0b" stroke-width="4" fill="#ffffff" fill-opacity="0.8"/><path d="M52 48l6 6" stroke="#f59e0b" stroke-width="5" stroke-linecap="round"/></svg>'
            ],
            'rocket-words' => [
                'title' => 'موشک کلمات',
                'bg'    => 'linear-gradient(135deg, #dc2626 0%, #991b1b 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M32 8c-8 12-10 24-8 34l8-4 8 4c2-10 0-22-8-34z" fill="#ffffff" fill-opacity="0.95"/><circle cx="32" cy="24" r="5" fill="#dc2626"/><path d="M24 38l-8 10h8l4-4M40 38l8 10h-8l-4-4" fill="#fef08a"/><path d="M28 44c0 8 4 12 4 12s4-4 4-12z" fill="#f97316"/></svg>'
            ],
            'alphabet-soup' => [
                'title' => 'سوپ الفبا',
                'bg'    => 'linear-gradient(135deg, #ea580c 0%, #c2410c 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M10 28c0 16 14 24 22 24s22-8 22-24H10z" fill="#ffffff" fill-opacity="0.95"/><path d="M6 28h52" stroke="#fef08a" stroke-width="3" stroke-linecap="round"/><circle cx="24" cy="36" r="4" fill="#ea580c"/><circle cx="40" cy="36" r="4" fill="#16a34a"/><path d="M34 10l-6 18" stroke="#ffffff" stroke-width="4" stroke-linecap="round"/></svg>'
            ],
            'tashdid-hammer' => [
                'title' => 'چکش تشدید',
                'bg'    => 'linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M20 18l14-10 8 10-14 10z" fill="#ffffff" fill-opacity="0.95"/><rect x="30" y="24" width="6" height="28" rx="3" transform="rotate(-35 30 24)" fill="#fef08a"/><text x="46" y="24" font-size="20" font-family="sans-serif" font-weight="bold" fill="#f59e0b">ّ</text></svg>'
            ],
            'train-mechanic' => [
                'title' => 'تعمیرگاه قطار کلمات',
                'bg'    => 'linear-gradient(135deg, #059669 0%, #047857 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="12" y="22" width="28" height="22" rx="4" fill="#ffffff" fill-opacity="0.95"/><path d="M40 28h12v16H40z" fill="#fef08a"/><circle cx="20" cy="46" r="5" fill="#047857"/><circle cx="34" cy="46" r="5" fill="#047857"/><circle cx="46" cy="46" r="5" fill="#047857"/><path d="M16 12v10h10V12" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/></svg>'
            ],
            'weather-station' => [
                'title' => 'ایستگاه هواشناسی',
                'bg'    => 'linear-gradient(135deg, #0891b2 0%, #0e7490 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="24" cy="24" r="10" fill="#facc15"/><path d="M24 38c-6 0-10-4-10-8a8 8 0 0 1 14-4 10 10 0 0 1 18 4c0 4-4 8-8 8H24z" fill="#ffffff" fill-opacity="0.95"/><path d="M28 44l-2 6M36 44l-2 6M44 44l-2 6" stroke="#38bdf8" stroke-width="3" stroke-linecap="round"/></svg>'
            ],
            'z-family' => [
                'title' => 'خانواده صدای ز',
                'bg'    => 'linear-gradient(135deg, #4f46e5 0%, #3730a3 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="32" cy="32" r="22" fill="#ffffff" fill-opacity="0.95"/><text x="22" y="28" font-size="14" font-family="sans-serif" font-weight="bold" fill="#4f46e5">ز</text><text x="42" y="28" font-size="14" font-family="sans-serif" font-weight="bold" fill="#ec4899">ض</text><text x="22" y="44" font-size="14" font-family="sans-serif" font-weight="bold" fill="#059669">ذ</text><text x="42" y="44" font-size="14" font-family="sans-serif" font-weight="bold" fill="#f59e0b">ظ</text></svg>'
            ],
            'recycle-letters' => [
                'title' => 'بازیافت نشانه‌ها',
                'bg'    => 'linear-gradient(135deg, #16a34a 0%, #15803d 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M18 20h28l-4 32H22L18 20z" fill="#ffffff" fill-opacity="0.95"/><path d="M14 20h36" stroke="#fef08a" stroke-width="4" stroke-linecap="round"/><path d="M32 26v16M26 34l6 6 6-6" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            ],
            'bubble-letters' => [
                'title' => 'حباب‌ترکانی نشانه‌ها',
                'bg'    => 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="24" cy="36" r="14" fill="#ffffff" fill-opacity="0.9"/><circle cx="44" cy="22" r="12" fill="#fef08a"/><circle cx="44" cy="44" r="8" fill="#ffffff" fill-opacity="0.75"/><text x="24" y="41" font-size="14" font-family="sans-serif" font-weight="bold" fill="#2563eb" text-anchor="middle">آ</text><text x="44" y="27" font-size="12" font-family="sans-serif" font-weight="bold" fill="#db2777" text-anchor="middle">ب</text></svg>'
            ],
            'travel-letter-ein' => [
                'title' => 'سفر چهارگانه نشانه ع',
                'bg'    => 'linear-gradient(135deg, #9333ea 0%, #7e22ce 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="32" cy="32" r="22" fill="#ffffff" fill-opacity="0.95"/><text x="32" y="38" font-size="24" font-family="sans-serif" font-weight="bold" fill="#9333ea" text-anchor="middle">ع</text><circle cx="16" cy="20" r="3" fill="#f59e0b"/><circle cx="48" cy="20" r="3" fill="#ec4899"/><circle cx="16" cy="44" r="3" fill="#06b6d4"/><circle cx="48" cy="44" r="3" fill="#10b981"/></svg>'
            ],
            'party-letters' => [
                'title' => 'مهمانی حروف و نقاط',
                'bg'    => 'linear-gradient(135deg, #db2777 0%, #be185d 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><polygon points="32,8 20,38 44,38" fill="#ffffff" fill-opacity="0.95"/><circle cx="32" cy="8" r="3" fill="#fef08a"/><circle cx="20" cy="46" r="3.5" fill="#fef08a"/><circle cx="32" cy="46" r="3.5" fill="#fef08a"/><circle cx="44" cy="46" r="3.5" fill="#fef08a"/><path d="M12 20c4 2 8 0 10-4M42 20c4 2 8 0 10-4" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round"/></svg>'
            ],
            'tea-house-words' => [
                'title' => 'چای‌خانه سنتی کلمات',
                'bg'    => 'linear-gradient(135deg, #b45309 0%, #78350f 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="22" y="24" width="20" height="26" rx="8" fill="#ffffff" fill-opacity="0.95"/><rect x="24" y="26" width="16" height="18" rx="6" fill="#f59e0b"/><path d="M42 32c4 0 6 4 6 7s-2 7-6 7" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/><path d="M28 16c0-4 4-6 4-10M36 16c0-4 4-6 4-10" stroke="#fef08a" stroke-width="2.5" stroke-linecap="round"/></svg>'
            ],
            'key-lock-words' => [
                'title' => 'کلید و قفل کلمات',
                'bg'    => 'linear-gradient(135deg, #ca8a04 0%, #854d0e 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="14" y="26" width="36" height="26" rx="6" fill="#ffffff" fill-opacity="0.95"/><path d="M22 26V18a10 10 0 0 1 20 0v8" stroke="#ffffff" stroke-width="4.5" stroke-linecap="round"/><circle cx="32" cy="37" r="4" fill="#ca8a04"/><path d="M32 41v5" stroke="#ca8a04" stroke-width="3" stroke-linecap="round"/></svg>'
            ],
                        'hedyeh1-nemat-khoda' => [
                'title' => 'باغ نعمت‌های زیبای خداوند',
                'bg'    => 'linear-gradient(135deg, #d946ef 0%, #a21caf 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="32" cy="32" r="16" fill="#fef08a"/><path d="M32 6v8M32 50v8M6 32h8M50 32h8M14 14l6 6M44 44l6 6M14 50l6-6M44 20l6-6" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round"/><circle cx="32" cy="32" r="8" fill="#f59e0b"/></svg>'
            ],
            'hedyeh1-khanevadeh-mehraban' => [
                'title' => 'خانواده مهربان من',
                'bg'    => 'linear-gradient(135deg, #10b981 0%, #047857 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M32 10L12 28h8v24h24V28h8L32 10z" fill="#ffffff" fill-opacity="0.95"/><path d="M32 26c-4-4-10-1-10 4 0 6 10 12 10 12s10-6 10-12c0-5-6-8-10-4z" fill="#f43f5e"/><rect x="28" y="38" width="8" height="14" rx="2" fill="#047857"/></svg>'
            ],
            'hedyeh1-doostan-khoob' => [
                'title' => 'دوستان خوب و مهربانی',
                'bg'    => 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><circle cx="22" cy="24" r="10" fill="#ffffff" fill-opacity="0.95"/><circle cx="42" cy="24" r="10" fill="#fef08a"/><path d="M12 50c0-6 6-10 12-10 4 0 8 2 10 6 2-4 6-6 10-6 6 0 12 4 12 10H12z" fill="#ffffff" fill-opacity="0.9"/><circle cx="32" cy="40" r="4" fill="#ef4444"/></svg>'
            ],
            'hedyeh1-heyvanat-tabiat' => [
                'title' => 'مهربانی با حیوانات و طبیعت',
                'bg'    => 'linear-gradient(135deg, #ca8a04 0%, #854d0e 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M18 48c14-20 28-20 28-20s-4 14-16 22H18z" fill="#ffffff" fill-opacity="0.95"/><circle cx="42" cy="22" r="5" fill="#fef08a"/><path d="M42 22l6-2M38 18l4 4" stroke="#ffffff" stroke-width="2"/><circle cx="26" cy="30" r="3" fill="#854d0e"/><circle cx="20" cy="24" r="2.5" fill="#854d0e"/><circle cx="32" cy="24" r="2.5" fill="#854d0e"/></svg>'
            ],
            'hedyeh1-payambaran-masjed' => [
                'title' => 'خانه پاک خدا و پیامبر مهربانی',
                'bg'    => 'linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><path d="M32 10c-8 6-12 14-12 20h24c0-6-4-14-12-20z" fill="#fef08a"/><rect x="14" y="30" width="36" height="24" rx="4" fill="#ffffff" fill-opacity="0.95"/><path d="M26 54V40c0-3 3-6 6-6s6 3 6 6v14H26z" fill="#5b21b6"/><circle cx="32" cy="8" r="2.5" fill="#fef08a"/><path d="M32 4v4" stroke="#fef08a" stroke-width="2"/></svg>'
            ],
            'default' => [
                'title' => 'آیکون پیش‌فرض بازی',
                'bg'    => 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',
                'svg'   => '<svg viewBox="0 0 64 64" width="48" height="48" fill="none"><rect x="12" y="16" width="40" height="32" rx="8" fill="#ffffff" fill-opacity="0.95"/><circle cx="24" cy="32" r="4" fill="#0284c7"/><circle cx="40" cy="32" r="4" fill="#0284c7"/><path d="M32 24v16M24 32h16" stroke="#fbbf24" stroke-width="3" stroke-linecap="round"/></svg>'
            ]
        ];
    }

    /**
     * Get game icon and background gradient for any post ID
     */
    public static function get_game_icon($post = null) {
        $post = get_post($post);
        if (!$post) {
            $presets = self::get_presets();
            return $presets['default'];
        }

        $post_id = $post->ID;
        $saved_svg = get_post_meta($post_id, '_gk_game_icon_svg', true);
        $saved_bg  = get_post_meta($post_id, '_gk_game_bg_gradient', true);
        $preset_key = get_post_meta($post_id, '_gk_game_icon_preset', true);

        if (!empty($saved_svg) && !empty($saved_bg)) {
            return [
                'svg'    => $saved_svg,
                'bg'     => $saved_bg,
                'preset' => $preset_key ?: 'custom'
            ];
        }

        // If not saved, match against presets
        $presets = self::get_presets();
        $folder  = get_post_meta($post_id, '_gk_game_folder', true);
        $slug    = $post->post_name;

        if (!empty($preset_key) && isset($presets[$preset_key])) {
            return $presets[$preset_key];
        } elseif (!empty($folder) && isset($presets[$folder])) {
            return $presets[$folder];
        } elseif (!empty($slug) && isset($presets[$slug])) {
            return $presets[$slug];
        }

        return $presets['default'];
    }

    /**
     * Save an icon preset or custom SVG to a game post
     */
    public static function save_game_icon($post_id, $preset_key_or_svg, $bg_gradient = '', $preset_slug = '') {
        $presets = self::get_presets();

        if (isset($presets[$preset_key_or_svg])) {
            $p = $presets[$preset_key_or_svg];
            update_post_meta($post_id, '_gk_game_icon_svg', $p['svg']);
            update_post_meta($post_id, '_gk_game_bg_gradient', $p['bg']);
            update_post_meta($post_id, '_gk_game_icon_preset', $preset_key_or_svg);
            return true;
        }

        if (!empty($preset_key_or_svg)) {
            update_post_meta($post_id, '_gk_game_icon_svg', $preset_key_or_svg);
        }
        if (!empty($bg_gradient)) {
            update_post_meta($post_id, '_gk_game_bg_gradient', $bg_gradient);
        }
        if (!empty($preset_slug)) {
            update_post_meta($post_id, '_gk_game_icon_preset', $preset_slug);
        }
        return true;
    }

    /**
     * Render full tile thumbnail (with thumbnail image or SVG fallback)
     */
    public static function render_game_thumbnail_html($post_id, $size = 48, $extra_classes = '') {
        $icon = self::get_game_icon($post_id);
        $has_thumb = has_post_thumbnail($post_id);

        ob_start();
        ?>
        <div class="gk-card-thumb <?php echo esc_attr($extra_classes); ?>" style="background: <?php echo esc_attr($icon['bg']); ?>;">
            <?php if ($has_thumb): ?>
                <?php echo get_the_post_thumbnail($post_id, 'medium'); ?>
            <?php else: ?>
                <div class="gk-default-thumb">
                    <div class="gk-thumb-svg-circle">
                        <?php echo $icon['svg']; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Global helper function to get game icon & background
 */
function gk_get_game_icon($post = null) {
    return GK_Game_Assets::get_game_icon($post);
}

/**
 * Global helper function to render game thumbnail HTML
 */
function gk_render_game_thumb($post_id, $size = 48, $extra_classes = '') {
    return GK_Game_Assets::render_game_thumbnail_html($post_id, $size, $extra_classes);
}