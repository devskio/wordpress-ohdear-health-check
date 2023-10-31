<?php
const STATUS_OK = 'ok';
const STATUS_WARNING = 'warning';
const STATUS_FAILED = 'failed';
const STATUS_CRASHED = 'crashed';
const STATUS_SKIPPED = 'skipped';

function oh_dear_health_check_template_include($template) {
    if (get_query_var('oh_dear_health_check')) {
        $checkResults = array();

        oh_dear_check_secret();
        $checkResults['disk_space'] = get_used_disk_space();
        $checkResults['error_log'] = check_php_error_log_size();
        $checkResults['mysql_size'] = get_mysql_size();
        $checkResults['forgotten_files'] = scan_document_root_for_forgotten_files();
        $checkResults['wordpress_version'] = get_wordpress_version();

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($checkResults);
        exit;
    }
    return $template;
}
add_filter('template_include', 'oh_dear_health_check_template_include');

function oh_dear_check_secret() {
    $expectedValue = get_option('oh_dear_health_check_secret_key');

    // Check if the header exists
    if (isset($_SERVER['HTTP_OH_DEAR_HEALTH_CHECK_SECRET'])) {
        // Get the header value
        $headerValue = $_SERVER['HTTP_OH_DEAR_HEALTH_CHECK_SECRET'];

        if ($headerValue === $expectedValue) {
            // Authorized, continue execution
            return;
        }
    }

    // Unauthorized access
    status_header(401);
    exit;
}


/**
 * Function to calculate the percentage of used disk space from total space and return a health check result.
 *
 * @return \OhDear\HealthCheckResults\CheckResult
 */
function get_used_disk_space() {
    $totalSpace = @disk_total_space('/');
    $usedSpace = $totalSpace - @disk_free_space('/');

    // Handle the case when total space is zero
    if ($totalSpace == 0) {
        return create_health_check_result(
            'UsedDiskSpace',
            'Used disk space',
            STATUS_SKIPPED,
            0,
            'SKIPPED',
            ['disk_space_used_percentage' => '0%']
        );
    }

    // Calculate the percentage with 2 decimal points
    $percentage = ($usedSpace / $totalSpace) * 100;
    $usedSpaceInPercentage = round($percentage, 2); // Round to 2 decimal places

    // Set the status
    if (intval($usedSpaceInPercentage) > 90) {
        $status = STATUS_FAILED;
    } else if (intval($usedSpaceInPercentage) > 75) {
        $status = STATUS_WARNING;
    } else {
        $status = STATUS_OK;
    }

    return create_health_check_result(
        'UsedDiskSpace',
        'Used Disk Space',
        $status,
        'Disk usage: ' . $status . ' (' . $usedSpaceInPercentage . '% used)',
        $usedSpaceInPercentage . '%',
        ['disk_space_used_percentage' => $usedSpaceInPercentage]
    );
}

/**
 * Function to get the size of the PHP error log and add the health check result.
 *
 * @return \OhDear\HealthCheckResults\CheckResult
 */
function check_php_error_log_size(): \OhDear\HealthCheckResults\CheckResult {
    $errorLogPath = ini_get('error_log');
    $errorLogFilesizeReadable = 0;

    // Check if the error log file exists
    if (file_exists($errorLogPath)) {
        $errorLogFilesize = filesize($errorLogPath);

        // Format the filesize in a human-readable format
        $errorLogFilesizeReadable = format_bytes($errorLogFilesize);

        // Determine the status based on the filesize
        if ($errorLogFilesize > 524288000) { // 500 MB
            $status = STATUS_FAILED;
        } elseif ($errorLogFilesize > 52428800) { // 50 MB
            $status = STATUS_WARNING;
        } else {
            $status = STATUS_OK;
        }
    } else {
        $status = STATUS_SKIPPED;
    }

    return create_health_check_result(
        'PHPErrorLogSize',
        'PHP Error Log Size',
        $status,
        'Error Log Filesize: ' . $status . ' (' . $errorLogFilesizeReadable . ')',
        'Error Log Filesize: ' . $status . ' (' . $errorLogFilesizeReadable . ')',
        ['error_log_filesize' => $errorLogFilesizeReadable]
    );
}

/**
 * Function to get the size of MySQL database and add the health check result.
 *
 * @return \OhDear\HealthCheckResults\CheckResult
 */
function get_mysql_size() {
    global $wpdb;

    $databaseName = $wpdb->dbname;

    // Get the database size
    $databaseSize = $wpdb->get_results(
        "SELECT table_schema AS 'Database',
        SUM(data_length + index_length) AS 'size'
        FROM information_schema.tables
        WHERE table_schema = '{$databaseName}'
        GROUP BY table_schema"
    );

    if ($databaseSize) {
        $sizeInBytes = (int) $databaseSize[0]->size;
        $sizeInMB = round($sizeInBytes / (1024 * 1024), 2);

        $status = STATUS_OK;

        if ($sizeInMB > 5242880000) {  // 5000 MB
            $status = STATUS_FAILED;
        } elseif ($sizeInMB > 4194304000) {  // 4000 MB
            $status = STATUS_WARNING;
        }

        // Get the 5 biggest tables
        $biggestTables = $wpdb->get_results(
            "SELECT table_name AS 'Table',
            ROUND((data_length + index_length) / (1024 * 1024), 2) AS 'Size'
            FROM information_schema.tables
            WHERE table_schema = '{$databaseName}'
            GROUP BY table_name
            ORDER BY 'Size' DESC
            LIMIT 5"
        );

        $biggestTablesArr = [];
        foreach ($biggestTables as $row) {
            $biggestTablesArr[$row->Table] = $row->Size . ' MB';
        }

        return create_health_check_result(
            'MysqlSize' . $databaseName,
            "Mysql Size ({$databaseName})",
            $status,
            'Database size: ' . $sizeInMB . ' MB',
            $sizeInMB . ' MB',
            ['biggest_tables' => $biggestTablesArr]
        );
    } else {
        $status = STATUS_SKIPPED;
        return create_health_check_result(
            'MysqlSize' . $databaseName,
            "Mysql Size ({$databaseName})",
            $status,
            'Database size: skipped',
            'SKIPPED',
            []
        );
    }
}

/**
 * Scan a specified folder for commonly forgotten files or folders by developers.
 * TODO: Refactor and use allowed filename patterns instead of disallowed
 *
 * @return \OhDear\HealthCheckResults\CheckResult
 */
function scan_document_root_for_forgotten_files() {
    $allowed_files = [
        '.htaccess',
        'index.php',
        'license.txt',
        'liesmich.html',
        'readme.html',
        'robots.txt',
        'wp-activate.php',
        'wp-admin',
        'wp-blog-header.php',
        'wp-comments-post.php',
        'wp-config.php',
        'wp-content',
        'wp-cron.php',
        'wp-includes',
        'wp-links-opml.php',
        'wp-load.php',
        'wp-login.php',
        'wp-mail.php',
        'wp-settings.php',
        'wp-signup.php',
        'wp-trackback.php',
        'xmlrpc.php',
    ];

    $forgotten_files_list = array();
    $count = 0;

    $items = scandir(ABSPATH);

    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..') {
            if (!in_array($item, $allowed_files)) {
                // This file is not in the allowed files list, consider it forgotten
                $forgotten_files_list[] = $item;
                $count++;
            }
        }
    }

    if ($count > 0) {
        return create_health_check_result(
            'ForgottenFiles',
            'Forgotten Files',
            STATUS_FAILED,
            'Found ' . $count . ' forgotten files or folders',
            $count . ' forgotten files',
            ['forgotten_files_list' => $forgotten_files_list]
        );
    } else {
        return create_health_check_result(
            'ForgottenFiles',
            'Forgotten Files',
            STATUS_OK,
            'No forgotten files or folders found',
            'No forgotten files found',
            []
        );
    }
}

/**
 * Check if installed WordPress version is the latest.
 *
 * @return \OhDear\HealthCheckResults\CheckResult
 */
function get_wordpress_version() {
    include_once(ABSPATH . 'wp-admin/includes/update.php');
    $cur = \get_preferred_from_update_core();
    // Get the installed WordPress version
    $installed_version = get_bloginfo('version');
    $latest_version = $cur->current;
    if ($latest_version !== null) {
        if (version_compare($installed_version, $latest_version, '<')) {
            // An update is available
            return create_health_check_result(
                'WordPressVersion',
                'WordPress Version',
                STATUS_WARNING,
                'Update available: Installed WordPress version is ' . $installed_version . ', Latest version is ' . $latest_version,
                STATUS_WARNING,
                ['installed_version' => $installed_version, 'latest_version' => $latest_version]
            );
        } else {
            // WordPress is up to date
            return create_health_check_result(
                'WordPressVersion',
                'WordPress Version',
                STATUS_OK,
                'Installed WordPress version ' . $installed_version . ' is up to date',
                STATUS_OK,
                ['installed_version' => $installed_version]
            );
        }
    } else {
        return create_health_check_result(
            'WordPressVersion',
            'WordPress Version',
            STATUS_CRASHED,
            'Error fetching latest WordPress version',
            STATUS_CRASHED,
            []
        );
    }
}

/**
 * Format bytes into a human-readable format.
 *
 * @param float $bytes Number of bytes.
 * @param int $precision Number of decimal places.
 * @return string
 */
function format_bytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Helper function to create the health check result in the required format.
 *
 * @param string $name
 * @param string $label
 * @param string $status
 * @param string $notificationMessage
 * @param mixed $shortSummary
 * @param array $meta
 * @return \OhDear\HealthCheckResults\CheckResult
 */
function create_health_check_result(
    string $name,
    string $label,
    string $status,
    string $notification_message,
    mixed $short_summary,
    array $meta
): \OhDear\HealthCheckResults\CheckResult {
    return new \OhDear\HealthCheckResults\CheckResult(
        name: $name,
        label: $label,
        notificationMessage: $notification_message,
        shortSummary: $short_summary,
        status: $status,
        meta: $meta
    );
}




