import os
from PIL import Image
import shutil

def get_image_info(path):
    try:
        with Image.open(path) as img:
            return img.size, img.format
    except Exception:
        return None, None

def main():
    src_folder = r'C:\Users\pools\WP-Pool-Safe-Portal\Pool-Safe-Portal\LounGenie Photos'
    out_folder = 'curated_images'
    os.makedirs(out_folder, exist_ok=True)
    summary = []
    for fname in os.listdir(src_folder):
        if fname.lower().endswith((".jpg", ".jpeg", ".png", ".webp", ".avif")):
            fpath = os.path.join(src_folder, fname)
            size, fmt = get_image_info(fpath)
            if size and fmt:
                width, height = size
                if width >= 1200 and height >= 800:
                    # Copy high-quality images to curated folder
                    shutil.copy2(fpath, os.path.join(out_folder, fname))
                    summary.append(f"{fname}: {width}x{height} {fmt}")
    with open('curated_images_summary.txt', 'w', encoding='utf-8') as f:
        f.write('\n'.join(summary))
    print('Curated images copied and summary saved.')

if __name__ == '__main__':
    main()
