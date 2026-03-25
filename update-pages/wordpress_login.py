import requests
from bs4 import BeautifulSoup

# WordPress login credentials
login_url = "https://loungenie.com/Loungenie™/wp-login.php"
username = "copilot"
password = "2m$RX0kkSykqGFj^25Nl@tPg"

# Start a session
session = requests.Session()

# Get the login page
login_page = session.get(login_url)
soup = BeautifulSoup(login_page.content, 'html.parser')

# Find the login form and hidden fields
login_data = {
    'log': username,
    'pwd': password,
    'wp-submit': 'Log In',
    'redirect_to': 'https://loungenie.com/Loungenie™/wp-admin/',
    'testcookie': '1'
}

# Perform the login
response = session.post(login_url, data=login_data)

# Check if login was successful
if "Dashboard" in response.text:
    print("Login successful! Accessing the admin dashboard...")
else:
    print("Login failed. Please check credentials or site status.")

# Further actions can be added here, such as reviewing content or making changes.