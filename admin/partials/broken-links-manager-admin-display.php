<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.agence-digitalink.fr/
 * @since      1.0.0
 *
 * @package    Broken_Links_Manager
 * @subpackage Broken_Links_Manager/admin/partials
 */
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div id="blm-actions">
        <button id="blm-scan-links" class="button button-primary">Scan for Links</button>
    </div>

    <div id="blm-results">
        <h2>Scanned Links</h2>
        <div id="blm-bulk-actions">
            <select id="blm-bulk-action">
                <option value="">Bulk Actions</option>
                <option value="remove">Remove Selected</option>
            </select>
            <button id="blm-apply-bulk-action" class="button">Apply</button>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><input type="checkbox" id="blm-select-all"></th>
                    <th>Post ID</th>
                    <th>URL</th>
                    <th>Status Code</th>
                    <th>Found Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="blm-results-body">
                <!-- Results will be populated here via JavaScript -->
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