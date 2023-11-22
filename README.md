# WordPress OhDear Health Check Plugin

The WordPress OhDear Health Check Plugin allows you to monitor the health and performance of your WordPress application and server using [Oh Dear](https://ohdear.app/). With this plugin, you can receive alerts and notifications for critical issues, ensuring the smooth operation of your WordPress application.

## Features

- Forgotten Files: Scans the document root for forgotten files.
- MySQL Size: Checks the size of the MySQL database.
- PHP Error Log Size: Checks the size of the PHP error log.
- Used Disk Space: Monitors the disk space usage of your server.
- WordPress Version: Retrieves the installed WordPress version.

## Requirements

- WordPress version 5.8 or later.
- An active Oh Dear account with the necessary API credentials.

## Installation

1. Download the zip file from GitHub.
2. Go to Plugins > Add New > Upload Plugin.
3. Upload the zip file and activate the plugin.
4. Once installed, go to the plugin settings and provide your Oh Dear API credentials.
5. Re-save the permalinks to ensure the health check endpoint is registered via [https://yourdomain.com/healthcheck](https://yourdomain.com/healthcheck).

## Usage

1. After installing and configuring the plugin, you can access the OhDear Health Check dashboard.
2. The dashboard displays the current status of various monitored aspects, such as Forgotten Files, MySQL Size, PHP Error Log Size, Used Disk Space, WordPress Version.
3. Configure the desired alert thresholds and notification settings in Oh Dear.
4. When an issue is detected, you will receive alerts through your preferred communication channels (e.g., email, Slack, SMS) based on your Oh Dear configuration.

## Contributing

Contributions to the WordPress OhDear Health Check Plugin are welcome! If you encounter any bugs, have suggestions, or want to contribute new features, please submit a pull request or open an issue in the GitHub repository.

## License

This WordPress OhDear Health Check Plugin is released under the [MIT License](LICENSE).