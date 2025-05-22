# Shortcode Manager

**Version:** 1.0  
**Author:** shubham ralli

---

## Description

Shortcode Manager lets you create custom shortcodes via the WordPress admin. Define reusable PHP/HTML/JS/CSS code snippets as "Shortcode" custom post types and insert them anywhere with a simple shortcode:

\`\`\`plaintext
[sc name="your-slug"]
\`\`\`

You can also pass parameters to your shortcode, which become PHP variables inside your shortcode content.

---

## Features

- Create and manage shortcodes as a custom post type in WordPress admin
- Use a powerful code editor (CodeMirror) for shortcode content with syntax highlighting
- Support for PHP, HTML, JavaScript, and CSS inside shortcode content
- Pass parameters to shortcodes and access them as PHP variables
- Preview shortcode usage and copy the shortcode directly from the admin
- Clean admin UI with helpful meta boxes and usage instructions
- Shortcodes only execute if published, preventing broken code issues

---

## Installation

1. Upload the plugin files to the \`/wp-content/plugins/shortcode-manager\` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress admin.
3. Go to **Shortcodes** in the admin menu to add your first shortcode.

---

## Usage

### Creating a Shortcode

1. Add a new **Shortcode** post.
2. Enter a title (used as the shortcode slug).
3. Add your PHP/HTML/JS/CSS code in the **Shortcode Content** editor.
4. Publish the shortcode.

### Inserting Shortcodes

Use the shortcode in your posts, pages, or widgets:

\`\`\`plaintext
[sc name="your-slug"]
\`\`\`

### Passing Parameters

You can pass parameters which become PHP variables inside your shortcode code:

\`\`\`plaintext
[sc name="your-slug" title="Hello" color="#ff0000"]
\`\`\`

In your shortcode content, access them like this:

\`\`\`php
<?= \$title ?? 'Default Title'; ?>
<?= \$color ?? '#000000'; ?>
\`\`\`

---

## Developer Notes

- The shortcode content is executed via \`eval()\`. Make sure only trusted users can edit shortcode content.
- The plugin disables the default WordPress editor for the shortcode post type and uses a CodeMirror editor for better code editing.
- PHP errors inside shortcodes are caught and displayed in the frontend output.

---

## License

GPLv2 or later

---

## Contributing

Feel free to fork the repo and submit pull requests or issues!

---

## Author

shubham ralli 
