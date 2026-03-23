import requests
from bs4 import BeautifulSoup
import logging

# Enable logging for debugging
logging.basicConfig(level=logging.DEBUG)

# WordPress login credentials
login_url = "https://loungenie.com/Loungenie™/wp-login.php"
username = "copilot"
password = "2m$RX0kkSykqGFj^25Nl@tPg"

# Start a session
session = requests.Session()

# Get the login page
try:
    login_page = session.get(login_url)
    login_page.raise_for_status()
    logging.debug("Login page fetched successfully.")
except requests.exceptions.RequestException as e:
    logging.error(f"Failed to fetch login page: {e}")
    exit()

# Parse the login page
soup = BeautifulSoup(login_page.content, 'html.parser')
hidden_inputs = soup.find_all("input", {"type": "hidden"})
login_data = {input_tag["name"]: input_tag["value"] for input_tag in hidden_inputs if "name" in input_tag.attrs}

# Add username and password
login_data.update({
    'log': username,
    'pwd': password,
    'wp-submit': 'Log In',
    'redirect_to': 'https://loungenie.com/Loungenie™/wp-admin/',
    'testcookie': '1'
})

# Perform the login
try:
    response = session.post(login_url, data=login_data)
    response.raise_for_status()
    logging.debug("Login request sent successfully.")
except requests.exceptions.RequestException as e:
    logging.error(f"Login request failed: {e}")
    exit()

# Check if login was successful
if "Dashboard" in response.text:
    logging.info("Login successful! Accessing the admin dashboard...")
else:
    logging.warning("Login failed. Please check credentials or site status.")