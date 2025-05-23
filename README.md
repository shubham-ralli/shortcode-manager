# Shortcode Manager

**Shortcode Manager** is a WordPress plugin that lets you easily create and manage custom shortcodes through an intuitive admin interface. Write shortcode content using a syntax-highlighted editor and use it anywhere in your posts, pages, or widgets.

## Features

- ✨ Create and manage custom shortcodes from the WordPress admin  
- 🖊️ Integrated CodeMirror editor for syntax highlighting (HTML, PHP, CSS)  
- 🔍 Usage tracker shows where shortcodes are used  
- 🧩 Use shortcodes anywhere with `[sc name="your-slug"]`  
- ⚡ Lightweight and beginner-friendly  

## Installation

1. Upload the plugin folder to `/wp-content/plugins/shortcode-manager` or install via the WordPress dashboard.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to the **Shortcodes** menu in your dashboard to start creating shortcodes.

## Usage

To use a shortcode you created, insert it like this:

```php
[sc name="your-slug"]
```

Replace `your-slug` with the post slug of your custom shortcode.

## FAQ

### Can I write PHP, HTML, or CSS in the shortcode editor?

Yes! The CodeMirror editor allows you to write and highlight PHP, HTML, CSS, and JavaScript.

### How do I see where a shortcode is used?

Each shortcode shows the number of usages and provides a details view listing posts, pages, or widgets where it's used.

### Is it safe to write PHP inside the shortcode editor?

Yes, but be careful—executing PHP code comes with risks. Only trusted users should have access to the shortcode editor.

## Screenshots

> *(Add screenshots to your GitHub repo in the `/assets` directory and link them here.)*

1. **Shortcodes List:** Manage all your custom shortcodes.
2. **Editor Interface:** Write code with syntax highlighting.
3. **Usage View:** See where shortcodes are being used.

## Requirements

- WordPress 5.0 or higher  
- PHP 7.2 or higher  

## Changelog

### 1.0
- Initial release

## License

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

👨‍💻 Created by [Shubham Ralli](https://github.com/shubham-ralli)
