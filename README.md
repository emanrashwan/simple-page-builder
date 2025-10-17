# Simple Page Builder - WordPress Plugin

A secure WordPress plugin for creating bulk pages via REST API with advanced authentication and webhook notifications.

Features

- **Secure REST API Endpoint** - Create pages from external applications
- **API Key Authentication** - Production-ready authentication system
- **Rate Limiting** - Prevent API abuse with configurable rate limits
- **Webhook Notifications** - Get notified when pages are created
- **Activity Logging** - Track all API requests and responses
- **Admin Dashboard** - Comprehensive management interface
- **HMAC Signature Verification** - Secure webhook delivery

 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

 Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/emanrashwan/simple-page-builder.git
   cd simple-page-builder
   ```

2. **Upload to WordPress:**
   - Copy the plugin folder to `/wp-content/plugins/`
   - Or upload as a ZIP file through WordPress admin

3. **Activate the plugin:**
   - Go to Plugins → Installed Plugins
   - Find "Simple Page Builder" and click "Activate"

4. **Generate API Key:**
   - Go to Tools → Page Builder
   - Navigate to API Keys tab
   - Click "Generate New API Key"
   - Copy and save your API key securely

 API Documentation

### Endpoint

```
POST https://yoursite.com/wp-json/pagebuilder/v1/create-pages
```

### Authentication

Include your API key in the request header:

```
X-API-Key: your_api_key_here
```

Or using Bearer token:

```
Authorization: Bearer your_api_key_here
```

### Request Format

```json
{
  "pages": [
    {
      "title": "About Us",
      "content": "<p>This is the about page content</p>",
      "status": "publish",
      "slug": "about-us",
      "template": "default",
      "featured_image_url": "https://example.com/image.jpg"
    },
    {
      "title": "Contact",
      "content": "<p>Contact us here</p>",
      "status": "draft"
    }
  ]
}
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | Yes | Page title |
| `content` | string | No | Page content (HTML allowed) |
| `status` | string | No | Page status (publish, draft, private). Default: publish |
| `slug` | string | No | URL slug. Auto-generated if not provided |
| `template` | string | No | Page template filename |
| `featured_image_url` | string | No | URL of featured image to upload |

### Response Format

```json
{
  "success": true,
  "request_id": "req_abc123xyz",
  "total_requested": 2,
  "created": 2,
  "failed": 0,
  "pages": [
    {
      "id": 123,
      "title": "About Us",
      "url": "https://yoursite.com/about-us",
      "status": "publish"
    },
    {
      "id": 124,
      "title": "Contact",
      "url": "https://yoursite.com/contact",
      "status": "draft"
    }
  ],
  "response_time": 0.245
}
```

### Error Response

```json
{
  "code": "invalid_api_key",
  "message": "Invalid or expired API key",
  "data": {
    "status": 401
  }
}
```

cURL Examples

### Basic Request

```bash
curl -X POST https://yoursite.com/wp-json/pagebuilder/v1/create-pages \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "pages": [
      {
        "title": "Test Page",
        "content": "<p>This is a test page</p>",
        "status": "publish"
      }
    ]
  }'
```

### Multiple Pages

```bash
curl -X POST https://yoursite.com/wp-json/pagebuilder/v1/create-pages \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "pages": [
      {
        "title": "About Us",
        "content": "<h1>About Our Company</h1><p>We are awesome!</p>",
        "status": "publish",
        "slug": "about"
      },
      {
        "title": "Services",
        "content": "<h1>Our Services</h1><p>We offer the best services.</p>",
        "status": "publish"
      },
      {
        "title": "Contact",
        "content": "<h1>Contact Us</h1><p>Get in touch today!</p>",
        "status": "publish"
      }
    ]
  }'
```

### Health Check

```bash
curl https://yoursite.com/wp-json/pagebuilder/v1/status
```

Webhook Notifications

When pages are created successfully, the plugin sends a POST request to your configured webhook URL.

### Webhook Payload

```json
{
  "event": "pages_created",
  "timestamp": "2025-10-17T14:30:00Z",
  "request_id": "req_abc123xyz",
  "api_key_name": "Production Server",
  "total_pages": 2,
  "pages": [
    {
      "id": 123,
      "title": "About Us",
      "url": "https://yoursite.com/about-us"
    },
    {
      "id": 124,
      "title": "Contact",
      "url": "https://yoursite.com/contact"
    }
  ]
}
```

### Verifying Webhook Signatures

The plugin includes an `X-Webhook-Signature` header with each webhook request:

**PHP Example:**

```php
<?php
// Get the webhook payload
$payload = file_get_contents('php://input');

// Get the signature from headers
$received_signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];

// Your webhook secret (from plugin settings)
$secret = 'your_webhook_secret';

// Calculate the expected signature
$calculated_signature = hash_hmac('sha256', $payload, $secret);

// Verify the signature
if (hash_equals($calculated_signature, $received_signature)) {
    // Signature is valid - process the webhook
    $data = json_decode($payload, true);
    
    // Process the pages
    foreach ($data['pages'] as $page) {
        // Do something with the page data
        error_log("New page created: " . $page['title']);
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
} else {
    // Signature is invalid
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
}
```

**Node.js Example:**

```javascript
const crypto = require('crypto');
const express = require('express');
const app = express();

app.use(express.json());

app.post('/webhook', (req, res) => {
    const payload = JSON.stringify(req.body);
    const receivedSignature = req.headers['x-webhook-signature'];
    const secret = 'your_webhook_secret';
    
    const calculatedSignature = crypto
        .createHmac('sha256', secret)
        .update(payload)
        .digest('hex');
    
    if (crypto.timingSafeEqual(
        Buffer.from(receivedSignature),
        Buffer.from(calculatedSignature)
    )) {
        // Process the webhook
        console.log('Pages created:', req.body.pages);
        res.json({ status: 'success' });
    } else {
        res.status(401).json({ error: 'Invalid signature' });
    }
});

app.listen(3000);
```

Security Features

1. **API Key Hashing** - Keys are hashed (SHA-256) before storage
2. **Rate Limiting** - Configurable requests per hour per key
3. **Key Expiration** - Optional expiration dates for API keys
4. **Request Logging** - All requests are logged with IP addresses
5. **Signature Verification** - HMAC-SHA256 for webhook security
6. **Permission Checks** - WordPress capability checks for admin actions

 Configuration

### Settings (Tools → Page Builder → Settings)

- **Webhook URL** - URL to receive webhook notifications
- **Rate Limit** - Maximum requests per API key per hour (default: 100)
- **API Status** - Enable/disable API access globally
- **Default Key Expiration** - Default expiration for new keys

### Rate Limiting

The plugin enforces rate limits per API key. When a key exceeds the limit:

- Returns HTTP 429 (Too Many Requests)
- Logs the failed attempt
- Key remains active but cannot make requests until the hour window resets

 Admin Interface

### API Keys Tab
- Generate new API keys
- View all API keys (active and revoked)
- Revoke keys instantly
- See usage statistics per key

### Activity Log Tab
- View all API requests
- Filter by status, date, or API key
- Export logs as CSV
- Monitor response times

### Created Pages Tab
- View all pages created via API
- Direct links to edit pages
- Track which API key created each page

### Documentation Tab
- Complete API documentation
- cURL examples
- Webhook integration guide
- Authentication instructions

 Development

### File Structure

```
simple-page-builder/
├── simple-page-builder.php     # Main plugin file
├── README.md                    # Documentation
├── includes/
│   ├── class-api-endpoint.php  # REST API endpoint handler
│   ├── class-api-keys.php      # API key management
│   ├── class-webhook.php       # Webhook notification system
│   ├── class-admin-interface.php # Admin dashboard
│   └── class-logger.php        # Activity logging
├── admin/
│   ├── css/
│   │   └── admin-styles.css    # Admin styles
│   └── js/
│       └── admin-scripts.js    # Admin JavaScript
└── assets/
```

### Database Tables

The plugin creates four custom tables:

1. **wp_spb_api_keys** - Stores API keys and metadata
2. **wp_spb_activity_log** - Logs all API requests
3. **wp_spb_pages_created** - Tracks pages created via API
4. **wp_spb_webhook_log** - Logs webhook deliveries

### Hooks & Filters

```php
// Modify rate limit dynamically
add_filter('spb_rate_limit', function($limit, $api_key_id) {
    // Custom logic
    return $limit;
}, 10, 2);

// Modify webhook payload
add_filter('spb_webhook_payload', function($payload) {
    $payload['custom_field'] = 'custom_value';
    return $payload;
});

// Action after pages created
add_action('spb_pages_created', function($pages, $api_key_id) {
    // Custom logic
}, 10, 2);
```

 Testing

### Manual Testing

1. Generate an API key in the admin
2. Use cURL or Postman to make requests
3. Verify pages are created
4. Check activity logs
5. Test webhook delivery

### Test with Postman

Import this collection for quick testing:

```json
{
  "info": {
    "name": "Simple Page Builder API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Create Pages",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "X-API-Key",
            "value": "{{api_key}}"
          },
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"pages\": [\n    {\n      \"title\": \"Test Page\",\n      \"content\": \"<p>Test content</p>\",\n      \"status\": \"publish\"\n    }\n  ]\n}"
        },
        "url": {
          "raw": "{{base_url}}/wp-json/pagebuilder/v1/create-pages",
          "host": ["{{base_url}}"],
          "path": ["wp-json", "pagebuilder", "v1", "create-pages"]
        }
      }
    }
  ]
}
```

 Changelog

### Version 1.0.0 (2025-10-17)
- Initial release
- REST API endpoint for creating pages
- API key authentication system
- Webhook notifications
- Admin dashboard interface
- Activity logging
- Rate limiting

 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

 License

This plugin is licensed under the GPL v2 or later.

 Author

**Eman Rashwan**
- Website: https://yourwebsite.com
- GitHub: [@yourusername](https://github.com/emanrashwan)
- Email: eashedeman54@gmail.com

 Acknowledgments

- Built for WebOps technical assessment
- WordPress REST API framework
- Community feedback and testing

 Support

For questions or issues:
- Create an issue on GitHub
- Email: eashedeman54@gmail.com
- Documentation: [Plugin Documentation](https://yourwebsite.com/docs)

---

Made with ❤️ for WordPress
