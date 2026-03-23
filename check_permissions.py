#!/usr/bin/env python3
import requests, base64

username = 'Copilot'
password = 'PoolSafeInc21'

auth = {'Authorization': 'Basic ' + base64.b64encode(f'{username}:{password}'.encode()).decode()}

print('Testing authentication and permissions...\n')

# Test read
r_get = requests.get('https://www.loungenie.com/wp-json/wp/v2/pages/4701', headers=auth, timeout=30)
print(f'GET /pages/4701: {r_get.status_code}')

# Test write
r_post = requests.post(
    'https://www.loungenie.com/wp-json/wp/v2/pages/4701',
    headers=auth,
    json={'content': 'test'},
    timeout=30
)
print(f'POST /pages/4701: {r_post.status_code}')

if r_post.status_code == 401:
    print('  → 401 Unauthorized: User cannot edit pages')
    error_data = r_post.json()
    print(f'  Error: {error_data.get("message", "N/A")}')

# Check user info
r_user = requests.get(
    'https://www.loungenie.com/wp-json/wp/v2/users/me',
    headers=auth,
    timeout=30
)

if r_user.status_code == 200:
    user_data = r_user.json()
    name = user_data.get('name')
    user_id = user_data.get('id')
    roles = user_data.get('roles', [])
    print(f'\nUser: {name} (ID: {user_id})')
    print(f'Roles: {roles}')
    
    caps = user_data.get('capabilities', {})
    print('\nCapabilities:')
    print(f'  edit_posts: {caps.get("edit_posts", False)}')
    print(f'  edit_pages: {caps.get("edit_pages", False)}')
    print(f'  publish_pages: {caps.get("publish_pages", False)}')
    print(f'  edit_published_pages: {caps.get("edit_published_pages", False)}')
else:
    print(f'\nUser info fetch failed: {r_user.status_code}')
    if r_user.status_code == 401:
        error = r_user.json()
        print(f'Error: {error.get("message", "N/A")}')
