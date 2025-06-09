=== Lemon Squeezy for WooCommerce ===
Contributors: loquisoft
Tags: lemon squeezy, woocommerce, payment gateway, subscriptions, checkout, digital products, recurring payments
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.8.4
License: Commercial License
License URI: https://loquisoft.com/license

Integrates Lemon Squeezy payment gateway with WooCommerce for seamless one-time and subscription payments with full webhook support.

== Description ==

**Lemon Squeezy for WooCommerce** provides a complete payment gateway integration that allows you to accept payments through Lemon Squeezy's powerful platform. Perfect for digital products, SaaS subscriptions, and any WooCommerce store wanting Lemon Squeezy's advanced features.

### 🚀 Key Features

**Payment Processing**
* Accept one-time payments for simple and variable products
* Full subscription support with recurring billing
* Secure hosted checkout pages via Lemon Squeezy
* Test mode for development and testing

**WooCommerce Integration**
* Native WooCommerce payment gateway
* Support for simple products and variable products
* Automatic order status management
* Complete subscription lifecycle management

**Advanced Functionality**
* Real-time webhook processing for instant order updates
* Automatic payment completion and refund handling
* Support for multiple product variants
* Secure signature verification for webhooks

### 🔄 Subscription Management

Complete subscription lifecycle support:
* Subscription creation and activation
* Payment success and failure handling
* Subscription cancellation and resumption
* Plan changes and updates
* Automatic renewal processing
* Payment recovery for failed payments
* Subscription pausing and unpausing

### 🛠️ Easy Setup

1. **Configure API Settings**: Enter your Lemon Squeezy API key and Store ID
2. **Set Webhook URL**: Configure webhooks for real-time updates
3. **Product Mapping**: Assign Lemon Squeezy Variant IDs to your WooCommerce products
4. **Start Selling**: Your customers can now pay through Lemon Squeezy's secure checkout

### 💳 Supported Payment Types

* **One-time Payments**: Perfect for digital downloads, courses, and physical products
* **Recurring Subscriptions**: Ideal for SaaS, memberships, and subscription boxes
* **Variable Products**: Support for products with multiple pricing options
* **Mixed Carts**: Handle both one-time and subscription products

### 🔒 Security & Reliability

* Secure webhook signature verification
* PCI-compliant payment processing through Lemon Squeezy
* Comprehensive error handling and logging
* SSL/TLS encryption for all API communications

### 📊 Developer Friendly

* Comprehensive webhook event handling
* Detailed logging for debugging
* Clean, well-documented code
* Extensible architecture for custom modifications

== Installation ==

### Automatic Installation

1. Go to Plugins → Add New in your WordPress admin
2. Search for "Lemon Squeezy for WooCommerce"
3. Click Install and then Activate

### Manual Installation

1. Download the plugin ZIP file
2. Go to Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate the plugin

### Configuration

1. **Navigate to Settings**: Go to WooCommerce → Settings → Payments → Lemon Squeezy
2. **Enable the Gateway**: Check "Enable Lemon Squeezy Payment"
3. **Enter Credentials**:
   - API Key: Your Lemon Squeezy API key
   - Store ID: Your Lemon Squeezy store ID
   - Webhook Secret: Generate and enter a webhook secret
4. **Configure Options**:
   - Enable test mode for testing
   - Enable variable product support if needed
5. **Set Up Webhooks**: In your Lemon Squeezy dashboard, set the webhook URL to: `https://yoursite.com/wc-api/lemon_squeezy_webhook`
6. **Map Products**: For each product, enter the corresponding Lemon Squeezy Variant ID

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes, WooCommerce must be installed and active for this plugin to work.

= Do I need a Lemon Squeezy account? =

Yes, you need a Lemon Squeezy merchant account with API access to use this plugin.

= Can I use this with WooCommerce Subscriptions? =

Yes! The plugin has full support for WooCommerce Subscriptions and handles the complete subscription lifecycle.

= How do I set up webhooks? =

In your Lemon Squeezy dashboard, add a webhook endpoint pointing to: `https://yoursite.com/wc-api/lemon_squeezy_webhook` and include the webhook secret you configured in the plugin settings.

= What is a Variant ID and where do I find it? =

A Variant ID is Lemon Squeezy's identifier for each product/pricing option. You can find it in your Lemon Squeezy dashboard when viewing a product's variants.

= Can customers pay without leaving my site? =

The plugin uses Lemon Squeezy's hosted checkout, which means customers are redirected to Lemon Squeezy's secure payment pages and then returned to your site.

= Does this support refunds? =

Yes, the plugin handles refund webhooks from Lemon Squeezy and automatically updates order statuses in WooCommerce.

= Can I test payments before going live? =

Absolutely! Enable test mode in the plugin settings to use Lemon Squeezy's sandbox environment.

= What happens if a webhook fails? =

The plugin includes comprehensive logging. Check WooCommerce → Status → Logs for detailed webhook processing information.

= Is this compatible with other WooCommerce plugins? =

The plugin is designed to work seamlessly with the WooCommerce ecosystem, including most themes and extensions.

== Screenshots ==

1. Payment gateway settings page with API configuration options
2. Product edit screen showing Lemon Squeezy Variant ID field
3. Variable product variation settings with Variant ID mapping
4. Checkout page with Lemon Squeezy payment option
5. Order details showing Lemon Squeezy payment information
6. Webhook logs showing real-time payment processing

== Changelog ==

= 1.8.4 =
* Current stable release with full payment and subscription support
* Enhanced webhook processing and error handling
* Improved variable product support
* Better logging and debugging capabilities

== Upgrade Notice ==

= 1.8.4 =
This version includes important stability improvements and enhanced webhook processing. Update recommended for all users.

== Requirements ==

* WordPress 5.0 or higher
* WooCommerce 3.0 or higher
* WooCommerce Subscriptions (for subscription features)
* PHP 7.4 or higher
* SSL certificate (recommended for production)
* Active Lemon Squeezy merchant account

== Support ==

For support and documentation, please visit [Loquisoft.com](https://loquisoft.com) or contact our support team.

== Privacy ==

This plugin processes payment data through Lemon Squeezy's secure platform. Customer payment information is handled according to Lemon Squeezy's privacy policy and PCI compliance standards.
