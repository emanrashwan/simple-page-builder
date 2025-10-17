<?php
/**
 * API Documentation View
 */

if (!defined('ABSPATH')) {
    exit;
}

$site_url = get_site_url();
$endpoint_url = $site_url . '/wp-json/pagebuilder/v1/create-pages';
$status_url = $site_url . '/wp-json/pagebuilder/v1/status';
?>

<div class="spb-section">
    <h2>📚 API Documentation</h2>
    <p>Complete guide for integrating with the Simple Page Builder API.</p>
</div>

<!-- Quick Start -->
<div class="spb-section">
    <h3>🚀 Quick Start</h3>
    <ol style="line-height: 2;">
        <li>Generate an API key in the <a href="?page=simple-page-builder&tab=api-keys">API Keys tab</a></li>
        <li>Copy and securely store your API key</li>
        <li>Make API requests using the endpoint below</li>
        <li>Monitor requests in the <a href="?page=simple-page-builder&tab=activity-log">Activity Log</a></li>
    </ol>
</div>

<!-- Endpoint -->
<div class="spb-section">
    <h3>📡 API Endpoint</h3>
    <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; border: 1px solid #ddd;">
        <strong>POST</strong> <code style="font-size: 14px;"><?php echo esc_html($endpoint_url); ?></code>
        <button type="button" class="button button-small" style="float: right;" onclick="navigator.clipboard.writeText('<?php echo esc_js($endpoint_url); ?>').then(() => alert('Endpoint copied!'))">
            📋 Copy
        </button>
    </div>
    
    <h4 style="margin-top: 20px;">Health Check Endpoint</h4>
    <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; border: 1px solid #ddd;">
        <strong>GET</strong> <code style="font-size: 14px;"><?php echo esc_html($status_url); ?></code>
        <button type="button" class="button button-small" style="float: right;" onclick="navigator.clipboard.writeText('<?php echo esc_js($status_url); ?>').then(() => alert('Endpoint copied!'))">
            📋 Copy
        </button>
    </div>
    <p class="description">Use the health check endpoint to verify the API is operational (no authentication required).</p>
</div>

<!-- Authentication -->
<div class="spb-section">
    <h3>🔐 Authentication</h3>
    <p>Include your API key in the request header using one of these methods:</p>
    
    <h4>Method 1: X-API-Key Header (Recommended)</h4>
    <pre><code>X-API-Key: your_api_key_here</code></pre>
    
    <h4>Method 2: Authorization Bearer Token</h4>
    <pre><code>Authorization: Bearer your_api_key_here</code></pre>
    
    <div style="margin-top: 15px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
        <strong>⚠️ Security Note:</strong> Never expose your API key in client-side code or public repositories. Always keep it secure on your server.
    </div>
</div>

<!-- Request Format -->
<div class="spb-section">
    <h3>📥 Request Format</h3>
    <p>Send a POST request with JSON payload containing an array of pages to create:</p>
    
    <pre><code>{
  "pages": [
    {
      "title": "About Us",
      "content": "&lt;h1&gt;About Our Company&lt;/h1&gt;&lt;p&gt;We are awesome!&lt;/p&gt;",
      "status": "publish",
      "slug": "about-us",
      "template": "default",
      "featured_image_url": "https://example.com/image.jpg"
    },
    {
      "title": "Contact",
      "content": "&lt;p&gt;Contact us today!&lt;/p&gt;",
      "status": "draft"
    }
  ]
}</code></pre>
    
    <h4 style="margin-top: 20px;">Request Parameters</h4>
    <table class="widefat fixed striped" style="margin-top: 10px;">
        <thead>
            <tr>
                <th style="width: 20%;">Parameter</th>
                <th style="width: 15%;">Type</th>
                <th style="width: 15%;">Required</th>
                <th style="width: 50%;">Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>title</code></td>
                <td>string</td>
                <td><span class="spb-badge spb-badge-danger">Yes</span></td>
                <td>Page title</td>
            </tr>
            <tr>
                <td><code>content</code></td>
                <td>string</td>
                <td><span class="spb-badge spb-badge-info">No</span></td>
                <td>Page content (HTML allowed)</td>
            </tr>
            <tr>
                <td><code>status</code></td>
                <td>string</td>
                <td><span class="spb-badge spb-badge-info">No</span></td>
                <td>Page status: <code>publish</code>, <code>draft</code>, <code>private</code> (default: publish)</td>
            </tr>
            <tr>
                <td><code>slug</code></td>
                <td>string</td>
                <td><span class="spb-badge spb-badge-info">No</span></td>
                <td>URL slug (auto-generated from title if not provided)</td>
            </tr>
            <tr>
                <td><code>template</code></td>
                <td>string</td>
                <td><span class="spb-badge spb-badge-info">No</span></td>
                <td>Page template filename (e.g., <code>template-fullwidth.php</code>)</td>
            </tr>
            <tr>
                <td><code>featured_image_url</code></td>
                <td>string</td>
                <td><span class="spb-badge spb-badge-info">No</span></td>
                <td>URL of image to download and set as featured image</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Response Format -->
<div class="spb-section">
    <h3>📤 Response Format</h3>
    
    <h4>Success Response (200 OK)</h4>
    <pre><code>{
  "success": true,
  "request_id": "req_abc123xyz",
  "total_requested": 2,
  "created": 2,
  "failed": 0,
  "pages": [
    {
      "id": 123,
      "title": "About Us",
      "url": "<?php echo esc_js($site_url); ?>/about-us",
      "status": "publish"
    },
    {
      "id": 124,
      "title": "Contact",
      "url": "<?php echo esc_js($site_url); ?>/contact",
      "status": "draft"
    }
  ],
  "response_time": 0.245
}</code></pre>
    
    <h4 style="margin-top: 20px;">Error Response</h4>
    <pre><code>{
  "code": "invalid_api_key",
  "message": "Invalid or expired API key",
  "data": {
    "status": 401
  }
}</code></pre>
</div>

<!-- Status Codes -->
<div class="spb-section">
    <h3>📊 HTTP Status Codes</h3>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 15%;">Code</th>
                <th style="width: 25%;">Status</th>
                <th style="width: 60%;">Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>200</code></td>
                <td><span class="spb-badge spb-badge-success">OK</span></td>
                <td>Request successful, pages created</td>
            </tr>
            <tr>
                <td><code>400</code></td>
                <td><span class="spb-badge spb-badge-danger">Bad Request</span></td>
                <td>Invalid request format or missing required fields</td>
            </tr>
            <tr>
                <td><code>401</code></td>
                <td><span class="spb-badge spb-badge-danger">Unauthorized</span></td>
                <td>Invalid, missing, or expired API key</td>
            </tr>
            <tr>
                <td><code>429</code></td>
                <td><span class="spb-badge spb-badge-warning">Too Many Requests</span></td>
                <td>Rate limit exceeded for this API key</td>
            </tr>
            <tr>
                <td><code>503</code></td>
                <td><span class="spb-badge spb-badge-warning">Service Unavailable</span></td>
                <td>API is currently disabled in settings</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- cURL Examples -->
<div class="spb-section">
    <h3>💻 cURL Examples</h3>
    
    <h4>Create Single Page</h4>
    <pre><code>curl -X POST <?php echo esc_html($endpoint_url); ?> \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "pages": [
      {
        "title": "About Us",
        "content": "&lt;h1&gt;About Us&lt;/h1&gt;&lt;p&gt;Learn more about our company.&lt;/p&gt;",
        "status": "publish"
      }
    ]
  }'</code></pre>
    
    <h4 style="margin-top: 20px;">Create Multiple Pages</h4>
    <pre><code>curl -X POST <?php echo esc_html($endpoint_url); ?> \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "pages": [
      {
        "title": "Services",
        "content": "&lt;p&gt;Our services&lt;/p&gt;",
        "status": "publish",
        "slug": "services"
      },
      {
        "title": "Contact",
        "content": "&lt;p&gt;Contact us&lt;/p&gt;",
        "status": "publish"
      },
      {
        "title": "Privacy Policy",
        "content": "&lt;p&gt;Privacy policy&lt;/p&gt;",
        "status": "draft"
      }
    ]
  }'</code></pre>
    
    <h4 style="margin-top: 20px;">Health Check</h4>
    <pre><code>curl <?php echo esc_html($status_url); ?></code></pre>
</div>

<!-- Webhook Documentation -->
<div class="spb-section">
    <h3>🔔 Webhook Notifications</h3>
    <p>When pages are successfully created, the plugin sends a POST request to your configured webhook URL.</p>
    
    <h4>Webhook Payload</h4>
    <pre><code>{
  "event": "pages_created",
  "timestamp": "2025-10-17T14:30:00Z",
  "request_id": "req_abc123xyz",
  "api_key_name": "Production Server",
  "total_pages": 2,
  "pages": [
    {
      "id": 123,
      "title": "About Us",
      "url": "<?php echo esc_js($site_url); ?>/about-us"
    },
    {
      "id": 124,
      "title": "Contact",
      "url": "<?php echo esc_js($site_url); ?>/contact"
    }
  ]
}</code></pre>
    
    <h4 style="margin-top: 20px;">Webhook Headers</h4>
    <pre><code>Content-Type: application/json
X-Webhook-Signature: abc123def456...
User-Agent: Simple-Page-Builder-Webhook/1.0</code></pre>
    
    <h4 style="margin-top: 20px;">Verifying Webhook Signatures (PHP)</h4>
    <pre><code>&lt;?php
// Get the payload and signature
$payload = file_get_contents('php://input');
$received_signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];

// Your webhook secret from plugin settings
$secret = 'your_webhook_secret_here';

// Calculate expected signature
$calculated_signature = hash_hmac('sha256', $payload, $secret);

// Verify using timing-safe comparison
if (hash_equals($calculated_signature, $received_signature)) {
    // Signature is valid - process the webhook
    $data = json_decode($payload, true);
    
    // Process pages...
    foreach ($data['pages'] as $page) {
        error_log("New page created: " . $page['title']);
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
} else {
    // Signature is invalid
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
}
?&gt;</code></pre>
    
    <h4 style="margin-top: 20px;">Verifying Webhook Signatures (Node.js)</h4>
    <pre><code>const crypto = require('crypto');
const express = require('express');
const app = express();

app.use(express.json());

app.post('/webhook', (req, res) => {
    const payload = JSON.stringify(req.body);
    const receivedSignature = req.headers['x-webhook-signature'];
    const secret = 'your_webhook_secret_here';
    
    const calculatedSignature = crypto
        .createHmac('sha256', secret)
        .update(payload)
        .digest('hex');
    
    if (crypto.timingSafeEqual(
        Buffer.from(receivedSignature),
        Buffer.from(calculatedSignature)
    )) {
        // Process webhook
        console.log('Pages created:', req.body.pages);
        res.json({ status: 'success' });
    } else {
        res.status(401).json({ error: 'Invalid signature' });
    }
});

app.listen(3000);</code></pre>
</div>

<!-- Code Examples -->
<div class="spb-section">
    <h3>🔧 Integration Examples</h3>
    
    <h4>PHP Example</h4>
    <pre><code>&lt;?php
$api_key = 'your_api_key_here';
$endpoint = '<?php echo esc_js($endpoint_url); ?>';

$pages = [
    [
        'title' => 'New Page',
        'content' => '&lt;p&gt;Page content here&lt;/p&gt;',
        'status' => 'publish'
    ]
];

$response = wp_remote_post($endpoint, [
    'headers' => [
        'Content-Type' => 'application/json',
        'X-API-Key' => $api_key
    ],
    'body' => json_encode(['pages' => $pages]),
    'timeout' => 30
]);

if (is_wp_error($response)) {
    echo 'Error: ' . $response->get_error_message();
} else {
    $body = json_decode(wp_remote_retrieve_body($response), true);
    echo 'Created: ' . $body['created'] . ' pages';
}
?&gt;</code></pre>
    
    <h4 style="margin-top: 20px;">Python Example</h4>
    <pre><code>import requests
import json

api_key = 'your_api_key_here'
endpoint = '<?php echo esc_js($endpoint_url); ?>'

headers = {
    'Content-Type': 'application/json',
    'X-API-Key': api_key
}

data = {
    'pages': [
        {
            'title': 'New Page',
            'content': '&lt;p&gt;Page content&lt;/p&gt;',
            'status': 'publish'
        }
    ]
}

response = requests.post(endpoint, headers=headers, json=data)
result = response.json()

print(f"Created {result['created']} pages")</code></pre>
    
    <h4 style="margin-top: 20px;">JavaScript (Node.js) Example</h4>
    <pre><code>const axios = require('axios');

const apiKey = 'your_api_key_here';
const endpoint = '<?php echo esc_js($endpoint_url); ?>';

const pages = [
    {
        title: 'New Page',
        content: '&lt;p&gt;Page content&lt;/p&gt;',
        status: 'publish'
    }
];

axios.post(endpoint, { pages }, {
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': apiKey
    }
})
.then(response => {
    console.log(`Created ${response.data.created} pages`);
})
.catch(error => {
    console.error('Error:', error.response.data);
});</code></pre>
</div>

<!-- Rate Limiting -->
<div class="spb-section">
    <h3>⏱️ Rate Limiting</h3>
    <p>Each API key has a rate limit configured in the <a href="?page=simple-page-builder&tab=settings">Settings</a>. 
    Current limit: <strong><?php echo esc_html(get_option('spb_rate_limit', 100)); ?> requests per hour</strong>.</p>
    
    <h4>Rate Limit Headers</h4>
    <p>Future versions will include rate limit information in response headers:</p>
    <pre><code>X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1634567890</code></pre>
    
    <h4>Rate Limit Exceeded Response</h4>
    <pre><code>{
  "code": "rate_limit_exceeded",
  "message": "Rate limit exceeded. Please try again later.",
  "data": {
    "status": 429,
    "retry_after": 3600
  }
}</code></pre>
</div>

<!-- Best Practices -->
<div class="spb-section">
    <h3>✅ Best Practices</h3>
    <ul style="line-height: 2;">
        <li><strong>Security:</strong> Store API keys securely using environment variables, never in code repositories</li>
        <li><strong>Error Handling:</strong> Always implement proper error handling for API requests</li>
        <li><strong>Validation:</strong> Validate and sanitize all data before sending to the API</li>
        <li><strong>Webhooks:</strong> Always verify webhook signatures to ensure authenticity</li>
        <li><strong>Rate Limits:</strong> Implement retry logic with exponential backoff for rate limit errors</li>
        <li><strong>Monitoring:</strong> Log all API interactions for debugging and monitoring</li>
        <li><strong>Testing:</strong> Use the health check endpoint to verify API availability before bulk operations</li>
        <li><strong>Batch Size:</strong> For better performance, create pages in reasonable batches (10-50 pages per request)</li>
    </ul>
</div>

<!-- Troubleshooting -->
<div class="spb-section">
    <h3>🔍 Troubleshooting</h3>
    
    <h4>Common Issues</h4>
    
    <details style="margin-bottom: 15px;">
        <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f9f9f9; border-radius: 4px;">
            ❌ "Invalid or expired API key" error
        </summary>
        <div style="padding: 15px; background: #fff; border: 1px solid #ddd; border-top: none;">
            <p><strong>Possible causes:</strong></p>
            <ul>
                <li>API key is incorrect or not properly copied</li>
                <li>API key has been revoked</li>
                <li>API key has expired</li>
                <li>Header format is incorrect</li>
            </ul>
            <p><strong>Solution:</strong> Verify your API key in the <a href="?page=simple-page-builder&tab=api-keys">API Keys tab</a> and ensure it's active.</p>
        </div>
    </details>
    
    <details style="margin-bottom: 15px;">
        <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f9f9f9; border-radius: 4px;">
            ❌ "Rate limit exceeded" error
        </summary>
        <div style="padding: 15px; background: #fff; border: 1px solid #ddd; border-top: none;">
            <p><strong>Solution:</strong> Wait for the rate limit window to reset (1 hour) or contact the site administrator to increase the rate limit.</p>
        </div>
    </details>
    
    <details style="margin-bottom: 15px;">
        <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f9f9f9; border-radius: 4px;">
            ❌ "API access is currently disabled" error
        </summary>
        <div style="padding: 15px; background: #fff; border: 1px solid #ddd; border-top: none;">
            <p><strong>Solution:</strong> The API has been disabled globally in the <a href="?page=simple-page-builder&tab=settings">Settings</a>. Enable it to allow requests.</p>
        </div>
    </details>
    
    <details style="margin-bottom: 15px;">
        <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f9f9f9; border-radius: 4px;">
            ❌ Pages not creating successfully
        </summary>
        <div style="padding: 15px; background: #fff; border: 1px solid #ddd; border-top: none;">
            <p><strong>Check:</strong></p>
            <ul>
                <li>Request format matches the documentation</li>
                <li>Title field is present (required)</li>
                <li>Content is properly escaped HTML</li>
                <li>WordPress user permissions are correct</li>
            </ul>
            <p>Review the <a href="?page=simple-page-builder&tab=activity-log">Activity Log</a> for detailed error messages.</p>
        </div>
    </details>
    
    <details style="margin-bottom: 15px;">
        <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f9f9f9; border-radius: 4px;">
            ❌ Webhook not receiving notifications
        </summary>
        <div style="padding: 15px; background: #fff; border: 1px solid #ddd; border-top: none;">
            <p><strong>Check:</strong></p>
            <ul>
                <li>Webhook URL is correctly configured in <a href="?page=simple-page-builder&tab=settings">Settings</a></li>
                <li>Webhook endpoint is accessible from the WordPress server</li>
                <li>Firewall rules allow outbound connections</li>
                <li>Webhook signature verification is implemented correctly</li>
            </ul>
            <p>Use the "Test Webhook" button in Settings to verify connectivity.</p>
        </div>
    </details>
</div>

<!-- Support -->
<div class="spb-section" style="background: #f0f6fc; border-left: 4px solid #0073aa;">
    <h3>💬 Need Help?</h3>
    <p>If you're experiencing issues or have questions:</p>
    <ul style="margin: 10px 0; padding-left: 20px;">
        <li>Check the <a href="?page=simple-page-builder&tab=activity-log">Activity Log</a> for detailed error messages</li>
        <li>Review this documentation for examples and best practices</li>
        <li>Test your API key with a simple cURL request</li>
        <li>Verify your webhook endpoint is accessible</li>
        <li>Contact your site administrator for API key or rate limit issues</li>
    </ul>
</div>

<!-- Download Examples -->
<div class="spb-section">
    <h3>📥 Download Resources</h3>
    <p>Get started quickly with these downloadable resources:</p>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px;">
        <div style="padding: 20px; background: #f9f9f9; border-radius: 8px; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 10px;">📮</div>
            <strong>Postman Collection</strong>
            <p style="color: #666; font-size: 13px;">Ready-to-use Postman collection with all endpoints</p>
            <button class="button" onclick="alert('Download the Postman collection from the GitHub repository!')">Download</button>
        </div>
        <div style="padding: 20px; background: #f9f9f9; border-radius: 8px; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 10px;">📄</div>
            <strong>Webhook Receiver</strong>
            <p style="color: #666; font-size: 13px;">Sample PHP webhook receiver with signature verification</p>
            <button class="button" onclick="alert('Download webhook-receiver-example.php from the repository!')">Download</button>
        </div>
        <div style="padding: 20px; background: #f9f9f9; border-radius: 8px; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 10px;">📚</div>
            <strong>Full Documentation</strong>
            <p style="color: #666; font-size: 13px;">Complete README with all documentation</p>
            <button class="button" onclick="alert('View README.md in the GitHub repository!')">View README</button>
        </div>
    </div>
</div>
