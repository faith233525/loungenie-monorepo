import requests
import csv
from requests.auth import HTTPBasicAuth
import json

# CONFIGURATION
WP_SITE = "https://loungenie.com/Loungenie%E2%84%A2"  # URL-encoded for ™
USERNAME = "copilot@loungenie.com"
APP_PASSWORD = "Qt0E Raef 9kMO k5GO FYsS 9j7D"  # Updated application password

# 1. Export all posts and pages
def fetch_all(endpoint):
    url = f"{WP_SITE}/wp-json/wp/v2/{endpoint}"
    items = []
    page = 1
    while True:
        resp = requests.get(url, params={"per_page": 100, "page": page}, auth=HTTPBasicAuth(USERNAME, APP_PASSWORD))
        if resp.status_code != 200:
            break
        data = resp.json()
        if not data:
            break
        items.extend(data)
        page += 1
    return items

def export_posts_pages():
    posts = fetch_all("posts")
    pages = fetch_all("pages")
    with open("wp_posts_pages.csv", "w", newline='', encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["type", "id", "title", "slug", "status", "date", "author", "excerpt", "content"])
        for post in posts:
            writer.writerow([
                "post", post["id"], post["title"]["rendered"], post["slug"], post["status"], post["date"], post["author"], post["excerpt"]["rendered"], post["content"]["rendered"]
            ])
        for page in pages:
            writer.writerow([
                "page", page["id"], page["title"]["rendered"], page["slug"], page["status"], page["date"], page["author"], page["excerpt"]["rendered"], page["content"]["rendered"]
            ])
    print("Exported posts and pages to wp_posts_pages.csv")

# 2. Export all images/media
def export_media():
    media = fetch_all("media")
    with open("wp_media.csv", "w", newline='', encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["id", "title", "slug", "media_type", "mime_type", "source_url", "filesize", "width", "height", "date", "author"])
        for m in media:
            details = m.get("media_details", {})
            writer.writerow([
                m["id"],
                m["title"]["rendered"],
                m["slug"],
                m["media_type"],
                m["mime_type"],
                m["source_url"],
                details.get("filesize"),
                details.get("width"),
                details.get("height"),
                m["date"],
                m["author"]
            ])
    print("Exported media to wp_media.csv")

# 3. Update pages with images and alt text
def update_pages():
    """Update WordPress pages with new images and alt text."""
    # Mapping of page titles to actual slugs from WordPress
    page_slug_mapping = {
        "Press Page": "press",
        "Home Page": "home",
        "Product/Features Page": "features",
        "Gallery Page": "gallery",
        "Testimonials Page": "testimonials",
        "Investors Page": "investors",
        "Contact Page": "contact"
    }

    with open("website_image_recommendations.txt", "r", encoding="utf-8") as f:
        recommendations = f.readlines()

    current_page = None
    for line in recommendations:
        line = line.strip()
        if line.startswith("## "):
            current_page = line[3:]
        elif line.startswith("- **Image:**") and current_page:
            image_url = line.split("**Image:** ")[1]
        elif line.startswith("- **Alt Text:**") and current_page:
            alt_text = line.split("**Alt Text:** ")[1]

            # Use the mapped slug for the current page
            slug = page_slug_mapping.get(current_page, None)
            if not slug:
                print(f"No slug mapping found for page: {current_page}")
                continue

            url = f"{WP_SITE}/wp-json/wp/v2/pages?slug={slug}"
            resp = requests.get(url, auth=HTTPBasicAuth(USERNAME, APP_PASSWORD))
            print(f"Fetching page: {slug}, Status Code: {resp.status_code}")  # Debug
            if resp.status_code == 200 and resp.json():
                page = resp.json()[0]
                page_id = page['id']

                # Update the page with the new image and alt text
                update_url = f"{WP_SITE}/wp-json/wp/v2/pages/{page_id}"
                content = page['content']['rendered']
                new_image_html = f'<img src="{image_url}" alt="{alt_text}" />'
                updated_content = content + "\n" + new_image_html

                update_resp = requests.post(update_url, 
                                            auth=HTTPBasicAuth(USERNAME, APP_PASSWORD),
                                            headers={"Content-Type": "application/json"},
                                            data=json.dumps({"content": updated_content}))

                print(f"Updating page: {current_page}, Status Code: {update_resp.status_code}")  # Debug
                if update_resp.status_code == 200:
                    print(f"Updated page: {current_page} with new image.")
                else:
                    print(f"Failed to update page: {current_page}. Error: {update_resp.status_code}")
            else:
                print(f"Page not found or error fetching: {current_page}")

if __name__ == "__main__":
    export_posts_pages()
    export_media()
    update_pages()
