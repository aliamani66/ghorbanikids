# -*- coding: utf-8 -*-
import os, glob, re, json, sys

sys.stdout.reconfigure(encoding='utf-8')

games = [
    ('balloon-path-3d-1.html', 'balloon-path-3d', 'مسیر بادکنک‌ها ۳ بعدی', 'مهارتی'),
    ('butterfly-flutter-1.html', 'butterfly-flutter', 'ترتیب پرواز پروانه‌ها', 'مهارتی'),
    ('candle_game.html', 'birthday-candle-3d', 'کیک تولد و شمع‌ها ۳ بعدی', 'مهارتی'),
    ('clock-hands-sequence-1.html', 'clock-hands-sequence', 'توالی جهت‌گیری عقربه‌ها', 'مهارتی'),
    ('coaster-windows-1.html', 'coaster-windows', 'پنجره‌های قطار شهربازی', 'مهارتی'),
    ('formula-card-game.html', 'symbol-color-pairs', 'کارت‌های وارونه با فرمول', 'مهارتی'),
    ('fruit-bowl-memory-3.html', 'fruit-bowl-memory', 'چینش میوه‌ها در کاسه', 'مهارتی'),
    ('index.html', 'emotion-recall-3d', 'صورت‌های خندان ۳ بعدی', 'مهارتی'),
    ('lollipop-match.html', 'lollipop-match', 'آب‌نبات‌چوبی‌های چرخشی', 'مهارتی'),
    ('rainy-umbrellas.html', 'rainy-umbrellas', 'چترهای بارانی', 'مهارتی'),
    ('rhythm-studio-1.html', 'rhythm-studio', 'استودیوی ریتمیک و ضرب‌آهنگ', 'مهارتی'),
    ('sound-sequence-memory-1.html', 'sound-sequence-memory', 'ردپای صدا و آواها', 'مهارتی'),
    ('stained-glass-pattern.html', 'stained-glass-pattern', 'الگوی شیشه‌های رنگی', 'مهارتی'),
    ('traffic-signs-matrix.html', 'traffic-signs-matrix', 'ماتریس نشانه‌های جاده‌ای', 'مهارتی'),
    ('traffic_light_game.html', 'traffic-light-memo-3d', 'ترتیب چراغ‌های راهنمایی ۳ بعدی', 'مهارتی'),
    ('harf-gomshode-2.html', 'harf-gomshode', 'بازی حرف گمشده - فارسی اول دبستان', 'درسی'),
    ('rocket-2.html', 'rocket-words', 'پرتاب موشک به کلمه درست - فارسی اول دبستان', 'درسی'),
    ('soup_cooking_v2-3.html', 'alphabet-soup', 'آشپزی با حروف الفبا - فارسی اول دبستان', 'درسی'),
    ('tashdid-hammer-1.html', 'tashdid-hammer', 'بازی چکش تشدیدزن نجّار و بنّا - فارسی اول دبستان', 'درسی'),
    ('train-mechanic.html', 'train-mechanic', 'تعمیرگاه قطار کلمات (تمایز ت و ط) - فارسی اول دبستان', 'درسی'),
    ('weather-station-1.html', 'weather-station', 'بازی ایستگاه هواشناسی - فارسی اول دبستان', 'درسی'),
    ('z-family-game.html', 'z-family', 'خانواده صدای «ز» - فارسی اول دبستان', 'درسی'),
    ('بازی-بازیافت-نشانه_ها-5.html', 'recycle-letters', 'بازی بازیافت نشانه‌ها - فارسی اول دبستان', 'درسی'),
    ('بازی-حباب-ترکانی-کامل.html', 'bubble-letters', 'بازی حباب‌ترکانی نشانه‌ها و کلمه‌ها - فارسی اول دبستان', 'درسی'),
    ('بازی-سفر-نشانه-ع-2.html', 'travel-letter-ein', 'بازی سفر چهارگانه نشانه «ع» - فارسی اول دبستان', 'درسی'),
    ('بازی-مهمانی-حروف-4.html', 'party-letters', 'مهمانی حروف و صندلی بازی - فارسی اول دبستان', 'درسی'),
    ('بازی-چایخانه-کلمات-4.html', 'tea-house-words', 'بازی چای‌خانه سنتی کلمات - فارسی اول دبستان', 'درسی'),
    ('کلید-و-قفل-کلمات-1.html', 'key-lock-words', 'کلید و قفل کلمات - فارسی اول دبستان', 'درسی'),
]

print(f'Total target games: {len(games)}')
for fname, slug, title, gtype in games:
    path = os.path.join(r'd:\work\ghorbanikids\new game 2', fname)
    if not os.path.exists(path):
        print(f'ERROR: Missing {fname}')
    else:
        print(f'OK: {fname} -> {slug} ({gtype})')
