
import os, glob, re, json, sys

sys.stdout.reconfigure(encoding='utf-8')
games_dir = r'd:\work\ghorbanikids\new game 2'
files = glob.glob(os.path.join(games_dir, '*.html'))

for f in sorted(files):
    name = os.path.basename(f)
    with open(f, 'r', encoding='utf-8', errors='ignore') as fp:
        content = fp.read()
    
    m_title = re.search(r'<title>(.*?)</title>', content, re.IGNORECASE)
    title = m_title.group(1).strip() if m_title else 'No Title'
    
    # find game over functions or alerts or modal displays
    go_funcs = re.findall(r'function\s+([a-zA-Z0-9_]+)\s*\([^)]*\)\s*\{[^}]{0,300}', content)
    interesting = [fn for fn in go_funcs if any(w in fn.lower() for w in ['gameover', 'end', 'finish', 'win', 'result', 'score', 'modal'])]
    
    print(f'FILE: {name}')
    print(f'TITLE: {title}')
    print(f'INTERESTING FUNCS: {interesting[:3]}')
    print('---')
