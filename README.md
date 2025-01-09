# Ghost Metrics WP

[![Project Status: Active – The project has reached a stable, usable state and is being actively developed.](https://www.repostatus.org/badges/latest/active.svg)](https://www.repostatus.org/#active)
[![License: GPL v3](https://img.shields.io/badge/License-GPL_v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)
[![PluginTerritory](https://img.shields.io/badge/Plugin%20Territory-Free-blue.svg?logo=wordpress)]()
[![Release Version](https://img.shields.io/github/release/GhostMetrics/ghost-metrics-wp.svg?color)](https://github.com/GhostMetrics/ghost-metrics-wp/releases/latest)

## Description

**Ghost Metrics WP** is a WordPress plugin that integrates your WordPress website with the Ghost Metrics platform, providing advanced analytics and tracking capabilities.

With this plugin, you can:
- Configure your Ghost Metrics instance securely within WordPress.
- Embed tracking scripts or Tag Manager for real-time data collection.
- Manage advanced tracking options, such as cross-domain linking, DoNotTrack compliance, and subdomain tracking.
- Enable seamless integration with Ghost Metrics for subdomains of `ghostmetrics.cloud`.

Ghost Metrics WP ensures your analytics remain private, secure, and compliant.

---

## Install Setup Packages

Install the necessary dependencies for development:

- `npm install && composer install`

## Development

### Start Development Server

Run the development server to watch for changes and rebuild assets:

- `npm run dev`

### Run PHPCS

Ensure your code adheres to the WordPress PHP coding standards:

- `composer cs` - Check for coding standard violations.
- `composer cbf` - Automatically fix coding standard violations.

## Build

To optimize and minify assets to the build folder with Webpack, run:

- `npm run build`

---

## Features

- **Secure Integration**: Add your Ghost Metrics URL and authentication token securely.
- **Custom Tracking Options**: Enable or disable advanced tracking features directly from the WordPress admin.
- **Flexible Embed Modes**: Choose between standard embed and Tag Manager integration.
- **Domain Validation**: Ensures only subdomains of `ghostmetrics.cloud` can be used, enhancing security.
- **Customizable Settings**: Tailor tracking options for specific sites or containers.

---

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Node.js (for asset builds)
- Composer (for PHP dependency management)

---

## License

This plugin is licensed under the [GPL v3](https://www.gnu.org/licenses/gpl-3.0.html). See the LICENSE file for more details.

---

## Support

For issues, questions, or feature requests, visit the [GitHub repository](https://github.com/GhostMetrics/ghost-metrics-wp/issues).

Happy tracking!