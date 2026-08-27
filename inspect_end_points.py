# -*- coding: utf-8 -*-
import os, glob, re, json, sys

sys.stdout.reconfigure(encoding='utf-8')

games = [
    ('balloon-path-3d-1.html', 'balloon-path-3d'),
    ('butterfly-flutter-1.html', 'butterfly-flutter'),
    ('candle_game.html', 'birthday-candle-3d'),
    ('clock-hands-sequence-1.html', 'clock-hands-sequence'),
    ('coaster-windows-1.html', 'coaster-windows'),
    ('formula-card-game.html', 'symbol-color-pairs'),
    ('fruit-bowl-memory-3.html', 'fruit-bowl-memory'),
    ('index.html', 'emotion-recall-3d'),
    ('lollipop-match.html', 'lollipop-match'),
    ('rainy-umbrellas.html', 'rainy-umbrellas'),
    ('rhythm-studio-1.html', 'rhythm-studio'),
    ('sound-sequence-memory-1.html', 'sound-sequence-memory'),
    ('stained-glass-pattern.html', 'stained-glass-pattern'),
    ('traffic-signs-matrix.html', 'traffic-signs-matrix'),
    ('traffic_light_game.html', 'traffic-light-memo-3d'),
    ('harf-gomshode-2.html', 'harf-gomshode'),
    ('rocket-2.html', 'rocket-words'),
    ('soup_cooking_v2-3.html', 'alphabet-soup'),
    ('tashdid-hammer-1.html', 'tashdid-hammer'),
    ('train-mechanic.html', 'train-mechanic'),
    ('weather-station-1.html', 'weather-station'),
    ('z-family-game.html', 'z-family'),
    ('بازی-بازیافت-نشانه_ها-5.html', 'recycle-letters'),
    ('بازی-حباب-ترکانی-کامل.html', 'bubble-letters'),
    ('بازی-سفر-نشانه-ع-2.html', 'travel-letter-ein'),
    ('بازی-مهمانی-حروف-4.html', 'party-letters'),
    ('بازی-چایخانه-کلمات-4.html', 'tea-house-words'),
    ('کلید-و-قفل-کلمات-1.html', 'key-lock-words'),
]

for fname, slug in games:
    path = os.path.join(r'd:\work\ghorbanikids\new game 2', fname)
    with open(path, 'r', encoding='utf-8', errors='ignore') as fp:
        content = fp.read()
    
    # search for score text and game over display
    lines = content.splitlines()
    snippet = []
    for i, l in enumerate(lines):
        if any(kw in l.lower() for kw in ['gameover', 'showgameover', 'finalscore', 'showendscreen', 'showwin', 'endgame', 'finishgame']):
            snippet.append(f'Line {i+1}: {l.strip()[:80]}')
    
    print(f'=== {slug} ({fname}) ===')
    print('\n'.join(snippet[:6]))
    print()
