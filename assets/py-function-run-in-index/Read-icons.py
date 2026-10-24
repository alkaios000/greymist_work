import os
import json

# Tentukan path dasar ke folder 'svgs'
base_dir = os.path.join('assets', 'fontawesome-free-6.7.2-web', 'svgs')

icons = []

# Iterasi setiap subfolder (kategori) di folder 'svgs'
for category in os.listdir(base_dir):
    category_path = os.path.join(base_dir, category)
    if os.path.isdir(category_path):
        # Iterasi file-file SVG di dalam kategori
        for filename in os.listdir(category_path):
            if filename.lower().endswith('.svg'):
                # Ambil nama file tanpa ekstensi
                name_without_ext = os.path.splitext(filename)[0]
                icons.append({
                    "name": name_without_ext,
                    "category": category,
                    "file": f"{category}/{filename}"
                })

# Simpan hasil ke file JSON
with open('icons.json', 'w', encoding='utf-8') as f:
    json.dump(icons, f, indent=2)

print("File icons.json telah dibuat dengan data ikon.")
