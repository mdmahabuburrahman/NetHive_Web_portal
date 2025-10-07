# NetHive Project

This project consists of a web-based portal for managing a network, built with PHP.

## NetHive Web Portal

The web portal provides a user interface for network management tasks, likely interacting with a MikroTik router based on the inclusion of the `routeros_api.class.php` library.

### System Requirements

*   **Operating System**: Any OS that can run a web server (e.g., Windows, macOS, Linux).
*   **Web Server**: An Apache web server is recommended, as the project includes `.htaccess` files for configuration.
*   **PHP**: PHP version 7.4 or higher is recommended.
*   **Web Browser**: A modern web browser is needed to access the portal.

### Frontend

The frontend is built with HTML, CSS, and JavaScript. The necessary JavaScript libraries (such as Bootstrap and Chart.js) are included in the `NetHive_Web_portal/assets` directory, so no additional installation is required.

### How to Run

1.  **Set up a web server**: Install an Apache web server with PHP support (version 7.4+ is recommended).
2.  **Copy project files**: Place the contents of the `NetHive_Web_portal` directory into the web root of your server (e.g., `htdocs` for XAMPP).
3.  **Access the portal**: Open a web browser and navigate to the server's address (e.g., `http://localhost`).

