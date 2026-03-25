import csv
from PIL import Image
import requests
from io import BytesIO
import os

# Load media data
input_file = "wp_media.csv"
output_folder = "optimized_images"

# Correct the INPUT_DIR to point to the actual directory containing images
INPUT_DIR = "C:\\Users\\pools\\Documents\\wordpress-develop\\images"  # Replace with the actual directory path containing images

# Ensure OUTPUT_DIR exists
if not os.path.exists(output_folder):
    os.makedirs(output_folder, exist_ok=True)

# Skip non-directory paths
if not os.path.isdir(INPUT_DIR):
    raise ValueError(f"The specified INPUT_DIR is not a directory: {INPUT_DIR}")

def download_image(url):
    response = requests.get(url)
    if response.status_code == 200:
        return Image.open(BytesIO(response.content))
    else:
        print(f"Failed to download {url}")
        return None

def optimize_image(image_path, output_path):
    with Image.open(image_path) as image:
        # Convert image to RGB if not already in a compatible mode
        if image.mode not in ("RGB", "L"):
            image = image.convert("RGB")
        
        # Resize image if larger than max dimensions
        if image.width > MAX_WIDTH or image.height > MAX_HEIGHT:
            image.thumbnail((MAX_WIDTH, MAX_HEIGHT))
        
        # Save optimized image
        image.save(output_path, "JPEG", optimize=True, quality=85)

def process_images():
    for image_path in os.listdir(input_file):
        input_path = os.path.join(input_file, image_path)
        output_path = os.path.join(output_folder, image_path)

        # Ensure the input path is a file before processing
        if os.path.isfile(input_path):
            optimize_image(input_path, output_path)
            print(f"Optimized and saved: {output_path}")

if __name__ == "__main__":
    process_images()