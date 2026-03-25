const fetch = require('node-fetch');
require('dotenv').config();

const WP_URL = process.env.WP_URL;
const WP_USERNAME = process.env.WP_USERNAME;
const WP_APP_PASSWORD = process.env.WP_APP_PASSWORD;

if (!WP_URL || !WP_USERNAME || !WP_APP_PASSWORD) {
  console.error('Missing required environment variables: WP_URL, WP_USERNAME, WP_APP_PASSWORD');
  process.exit(1);
}

const AUTH_HEADER = 'Basic ' + Buffer.from(`${WP_USERNAME}:${WP_APP_PASSWORD}`).toString('base64');

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

async function getPages() {
  const response = await fetch(`${WP_URL}/wp-json/wp/v2/pages`);
  if (!response.ok) {
    throw new Error(`Failed to fetch pages: ${response.statusText}`);
  }
  return response.json();
}

async function updatePage(id, content) {
  const response = await fetch(`${WP_URL}/wp-json/wp/v2/pages/${id}`, {
    method: 'POST',
    headers: {
      'Authorization': AUTH_HEADER,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ content }),
  });

  if (!response.ok) {
    console.error(`Failed to update page ${id}: ${response.statusText}`);
  } else {
    console.log(`Page ${id} updated successfully.`);
  }
}

function shouldSkip(title) {
  const skipKeywords = ['investor', 'press', 'financial', 'board'];
  return skipKeywords.some(keyword => title.toLowerCase().includes(keyword));
}

function detectType(title) {
  if (title.toLowerCase().includes('home')) return 'home';
  if (title.toLowerCase().includes('features')) return 'features';
  if (title.toLowerCase().includes('about')) return 'about';
  if (title.toLowerCase().includes('contact')) return 'contact';
  return 'generic';
}

function buildPremiumLayout(type, title) {
  switch (type) {
    case 'home':
      return `<!-- wp:group -->\n<!-- wp:heading -->Welcome to ${title}<!-- /wp:heading -->\n<!-- wp:paragraph -->Discover our amazing features.<!-- /wp:paragraph -->\n<!-- wp:button -->Click Here<!-- /wp:button -->\n<!-- /wp:group -->`;
    case 'features':
      return `<!-- wp:group -->\n<!-- wp:heading -->Features of ${title}<!-- /wp:heading -->\n<!-- wp:columns -->\n<!-- wp:column -->Feature 1<!-- /wp:column -->\n<!-- wp:column -->Feature 2<!-- /wp:column -->\n<!-- /wp:columns -->\n<!-- /wp:group -->`;
    case 'about':
      return `<!-- wp:group -->\n<!-- wp:heading -->About ${title}<!-- /wp:heading -->\n<!-- wp:paragraph -->Learn more about us.<!-- /wp:paragraph -->\n<!-- /wp:group -->`;
    case 'contact':
      return `<!-- wp:group -->\n<!-- wp:heading -->Contact ${title}<!-- /wp:heading -->\n<!-- wp:paragraph -->Get in touch with us.<!-- /wp:paragraph -->\n<!-- /wp:group -->`;
    default:
      return `<!-- wp:group -->\n<!-- wp:heading -->Welcome to ${title}<!-- /wp:heading -->\n<!-- wp:paragraph -->This is a generic page.<!-- /wp:paragraph -->\n<!-- /wp:group -->`;
  }
}

(async () => {
  try {
    const pages = await getPages();
    console.log(`Total pages found: ${pages.length}`);

    for (const page of pages) {
      const { id, title, content } = page;
      console.log(`Processing page: ${title.rendered}`);

      if (shouldSkip(title.rendered)) {
        console.log(`Skipping page: ${title.rendered}`);
        const wrappedContent = `<!-- wp:group -->\n${content.rendered}\n<!-- /wp:group -->`;
        await updatePage(id, wrappedContent);
      } else {
        const type = detectType(title.rendered);
        const newContent = buildPremiumLayout(type, title.rendered);
        await updatePage(id, newContent);
      }
    }
  } catch (error) {
    console.error(`Error: ${error.message}`);
  }
})();