import os
import json

base_dir = r"c:\laragon\www\Greymist_framework\Greymist (version 1.1 - refactoring_2)"
ignore_dirs_exact = {'fontawesome', 'font-awesome', 'sontawesome', 'greymist_css_var', 'img', 'shortable', 'sortable', 'v_env', 'db', 'backups', '.vscode', '.git', 'vendor', 'node_modules'}

stats = {
    'total_files': 0,
    'total_lines': 0,
    'total_blank': 0,
    'total_comments': 0,
    'total_code': 0,
    'by_extension': {}
}

for root, dirs, files in os.walk(base_dir):
    dirs[:] = [d for d in dirs if d.lower() not in ignore_dirs_exact]
    for file in files:
        ext = os.path.splitext(file)[1].lower()
        if ext not in ['.php', '.html', '.js', '.css', '.json', '.py', '.txt', '.md', '.sql']:
            continue
            
        path = os.path.join(root, file)
        try:
            with open(path, 'r', encoding='utf-8') as f:
                lines = f.readlines()
        except UnicodeDecodeError:
            try:
                with open(path, 'r', encoding='latin-1') as f:
                    lines = f.readlines()
            except:
                continue
                
        total = len(lines)
        blank = 0
        comment = 0
        code = 0
        
        in_block_comment = False
        block_end_marker = ''
        
        for line in lines:
            t = line.strip()
            if not t:
                blank += 1
                continue
                
            if in_block_comment:
                comment += 1
                if block_end_marker in t:
                    in_block_comment = False
            else:
                if ext in ['.php', '.js', '.css']:
                    if t.startswith('//') or (ext == '.php' and t.startswith('#')):
                        comment += 1
                    elif t.startswith('/*'):
                        comment += 1
                        if '*/' not in t[2:]:
                            in_block_comment = True
                            block_end_marker = '*/'
                    else:
                        code += 1
                elif ext == '.html':
                    if t.startswith('<!--'):
                        comment += 1
                        if '-->' not in t[4:]:
                            in_block_comment = True
                            block_end_marker = '-->'
                    else:
                        code += 1
                elif ext == '.py':
                    if t.startswith('#'):
                        comment += 1
                    elif (t.startswith('"""') or t.startswith("'''")) and len(t) >= 3:
                        comment += 1
                        marker = t[:3]
                        if t.count(marker) == 1:
                            in_block_comment = True
                            block_end_marker = marker
                    else:
                        code += 1
                else:
                    code += 1
                    
        stats['total_files'] += 1
        stats['total_lines'] += total
        stats['total_blank'] += blank
        stats['total_comments'] += comment
        stats['total_code'] += code
        
        if ext not in stats['by_extension']:
            stats['by_extension'][ext] = {'files':0, 'total':0, 'code':0, 'blank':0, 'comment':0}
        stats['by_extension'][ext]['files'] += 1
        stats['by_extension'][ext]['total'] += total
        stats['by_extension'][ext]['code'] += code
        stats['by_extension'][ext]['blank'] += blank
        stats['by_extension'][ext]['comment'] += comment
        
print(json.dumps(stats, indent=2))
