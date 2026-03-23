import csv

def improve_metadata():
    with open("wp_media.csv", "r", newline='', encoding="utf-8") as infile, open("wp_media_updated.csv", "w", newline='', encoding="utf-8") as outfile:
        reader = csv.DictReader(infile)
        fieldnames = reader.fieldnames + ["alt_text"]
        writer = csv.DictWriter(outfile, fieldnames=fieldnames)
        writer.writeheader()

        for row in reader:
            # Improve title if generic
            if row["title"].startswith("IMG_"):
                row["title"] = f"LounGenie image {row['id']}"

            # Add alt text
            row["alt_text"] = f"Image showing {row['title']}"

            writer.writerow(row)

improve_metadata()
print("Improved metadata and saved to wp_media_updated.csv")