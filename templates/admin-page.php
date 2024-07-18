<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div id="blm-actions">
        <button id="blm-scan-links" class="button button-primary">Scan for Broken Links</button>
    </div>

    <div id="blm-results">
        <h2>Broken Links</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Post ID</th>
                    <th>URL</th>
                    <th>Status Code</th>
                    <th>Found Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="blm-results-body">
                <?php
                $broken_links = $this->get_broken_links();
                foreach ($broken_links as $link) {
                    echo "<tr>";
                    echo "<td><a href\"{$link->post_id}\"></a>{$link->post_id}</td>";
                    echo "<td>{$link->status_code}</td>";
                    echo "<td>{$link->found_date}</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div id="blm-bulk-actions">
        <h2>Bulk Actions</h2>
        <select id="blm-status-code">
            <option value="403">403 Forbidden</option>
            <option value="404">404 Not Found</option>
            <option value="500">500 Server Error</option>
        </select>
        <button id="blm-bulk-remove" class="button">Remove All Links with Selected Status Code</button>
    </div>

    <div id="blm-logs">
        <h2>Logs</h2>
        <textarea id="blm-log-display" rows="10" readonly></textarea>
    </div>
</div>