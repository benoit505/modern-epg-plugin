# Modern EPG Plugin for WordPress

## Table of Contents
1. [Introduction](#introduction)
2. [Features](#features)
3. [Requirements](#requirements)
4. [Installation](#installation)
5. [Configuration](#configuration)
6. [Usage](#usage)
7. [Shortcodes](#shortcodes)
8. [Filters and Actions](#filters-and-actions)
9. [Customization](#customization)
10. [Troubleshooting](#troubleshooting)
11. [Frequently Asked Questions](#frequently-asked-questions)
12. [Changelog](#changelog)
13. [Contributing](#contributing)
14. [License](#license)
15. [Credits](#credits)

## Introduction

The Modern EPG Plugin is a powerful and flexible Electronic Program Guide (EPG) solution for WordPress. It allows you to display TV program schedules in a user-friendly, interactive grid format. This plugin is perfect for TV stations, streaming services, or any website that needs to showcase program schedules.

Our plugin fetches data from XML and M3U sources, processes it, and presents it in an easy-to-read format. It's designed with performance and customization in mind, making it suitable for both small and large-scale deployments.

## Features

- **Dynamic EPG Grid**: Display program schedules in a responsive, easy-to-navigate grid.
- **Multiple Data Sources**: Support for XML and M3U data formats.
- **Customizable Appearance**: Easily style the EPG to match your website's theme.
- **Channel Filtering**: Allow users to filter channels by groups or categories.
- **Current Time Indicator**: Highlight the current time in the EPG grid.
- **Program Details**: Show detailed information about each program on hover or click.
- **Shortcode Support**: Easily embed the EPG anywhere on your WordPress site.
- **Caching System**: Efficient caching to reduce server load and improve performance.
- **Responsive Design**: Looks great on both desktop and mobile devices.
- **Localization Ready**: Fully translatable for multi-language support.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
- Modern web browser with JavaScript enabled

## Installation

1. Download the `modern-epg-plugin.zip` file from the [releases page](https://github.com/benoit505/modern-epg-plugin/releases).
2. Log in to your WordPress admin panel.
3. Navigate to Plugins > Add New.
4. Click the "Upload Plugin" button at the top of the page.
5. Choose the `modern-epg-plugin.zip` file and click "Install Now".
6. After installation, click "Activate" to enable the plugin.

Alternatively, you can manually upload the plugin files to your server:

1. Unzip the `modern-epg-plugin.zip` file.
2. Using FTP, upload the `modern-epg-plugin` folder to your WordPress installation's `/wp-content/plugins/` directory.
3. Log in to your WordPress admin panel and navigate to the Plugins page.
4. Find "Modern EPG Plugin" in the list and click "Activate".

## Configuration

After activation, you'll need to configure the plugin settings:

1. Go to Settings > Modern EPG in your WordPress admin panel.
2. Enter the URLs for your XML and M3U data sources.
3. Configure the update frequency for the EPG data.
4. Customize the appearance settings as desired.
5. Click "Save Changes" to apply your settings.

## Usage

To display the EPG on your site, you can use the provided shortcode or Gutenberg block.

### Shortcode

Insert the following shortcode into any post or page where you want the EPG to appear:
### Gutenberg Block

1. In the Gutenberg editor, click the "+" button to add a new block.
2. Search for "Modern EPG" and select the block.
3. The EPG will be inserted into your post or page.

## Shortcodes

The plugin provides several shortcodes for flexibility:

- `[modern_epg]`: Displays the full EPG grid.
- `[modern_epg_channel id="123"]`: Shows the schedule for a specific channel.
- `[modern_epg_program id="456"]`: Displays details for a specific program.

## Filters and Actions

Developers can use the following filters and actions to customize the plugin's behavior:

- `modern_epg_channels`: Filter the list of channels before display.
- `modern_epg_programs`: Filter the list of programs before display.
- `modern_epg_render_channel`: Customize the rendering of individual channel items.
- `modern_epg_render_program`: Customize the rendering of individual program items.

Actions:
- `modern_epg_before_update`: Fires before the EPG data is updated.
- `modern_epg_after_update`: Fires after the EPG data has been updated.

## Customization

The plugin's appearance can be customized using CSS. The main EPG container has the class `modern-epg-container`. You can add custom CSS to your theme or use a custom CSS plugin to style the EPG to your liking.

For more advanced customization, you can override the plugin's template files by copying them to your theme directory:

1. Create a `modern-epg` folder in your theme directory.
2. Copy the template files from `/wp-content/plugins/modern-epg-plugin/templates/` to `/wp-content/themes/your-theme/modern-epg/`.
3. Modify the copied templates as needed.

## Troubleshooting

If you encounter issues with the plugin, try the following steps:

1. Ensure your WordPress and PHP versions meet the minimum requirements.
2. Deactivate all other plugins to check for conflicts.
3. Switch to a default WordPress theme to rule out theme-related issues.
4. Clear your browser cache and WordPress cache.
5. Check the WordPress error log for any relevant error messages.

If problems persist, please [open an issue](https://github.com/benoit505/modern-epg-plugin/issues) on our GitHub repository.

## Frequently Asked Questions

**Q: How often is the EPG data updated?**
A: By default, the plugin checks for updates every hour. You can adjust this in the plugin settings.

**Q: Can I use multiple EPGs on the same page?**
A: Yes, you can use the shortcode multiple times on a single page, each with different parameters if needed.

**Q: Is the plugin compatible with page builders?**
A: Yes, the Modern EPG Plugin is compatible with most popular page builders, including Elementor, Beaver Builder, and Divi.

## Changelog

### Version 2.0
- Implemented MVC architecture for improved code organization
- Added support for channel grouping and filtering
- Improved caching mechanism for better performance
- Enhanced error logging and debugging capabilities

### Version 1.5
- Added responsive design for mobile devices
- Implemented program details popup
- Improved accessibility features

### Version 1.0
- Initial release

## Contributing

We welcome contributions to the Modern EPG Plugin! If you'd like to contribute, please follow these steps:

1. Fork the repository on GitHub.
2. Create a new branch for your feature or bug fix.
3. Write your code and add tests if possible.
4. Submit a pull request with a clear description of your changes.

Please read our [Contributing Guidelines](CONTRIBUTING.md) for more details.

## License

The Modern EPG Plugin is licensed under the GPL v2 or later. See the [LICENSE](LICENSE) file for details.

## Credits

The Modern EPG Plugin is developed and maintained by Benoit505. We'd like to thank the following open-source projects and contributors:

- [SimpleXML](https://www.php.net/manual/en/book.simplexml.php) for XML parsing
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) for code quality guidelines
- All the contributors who have helped improve this plugin

---

For more information, feature requests, or support, please visit our [GitHub repository](https://github.com/benoit505/modern-epg-plugin)
