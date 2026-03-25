import csv
from collections import defaultdict

# Read wp_media.csv and group by title and slug
def read_media_file():
    media_data = []
    with open("wp_media.csv", "r", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        for row in reader:
            media_data.append(row)
    return media_data

def group_duplicates(media_data):
    duplicates = defaultdict(list)
    for item in media_data:
        key = (item['title'], item['slug'])
        duplicates[key].append(item)
    return duplicates

def select_best_image(duplicates):
    best_images = []
    for key, items in duplicates.items():
        best_image = max(items, key=lambda x: (int(x['width'] or 0), int(x['height'] or 0), int(x['filesize'] or 0)))
        best_images.append(best_image)
    return best_images

def save_best_images(best_images):
    with open("best_images.csv", "w", newline='', encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=best_images[0].keys())
        writer.writeheader()
        writer.writerows(best_images)

def main():
    media_data = read_media_file()
    duplicates = group_duplicates(media_data)
    best_images = select_best_image(duplicates)
    save_best_images(best_images)
    print("Best images saved to best_images.csv")

if __name__ == "__main__":
    main()