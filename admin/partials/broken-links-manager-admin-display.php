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
        <button id="blm-pre-scan" class="button button-primary">Start Pre-Scan</button>
        <button id="blm-stop-pre-scan" class="button" style="display:none;">Stop Pre-Scan</button>
        <button id="blm-check-links" class="button button-primary" style="display:none;">Check Links</button>
        <button id="blm-stop-check-links" class="button" style="display:none;">Stop Checking Links</button>
    </div>

    <div id="blm-progress" style="display:none;">
        <h3>Scan Progress</h3>
        <div id="blm-pre-scan-progress">
            <label>Posts Scanned:</label>
            <progress id="blm-posts-progress" value="0" max="100"></progress>
            <span id="blm-posts-progress-text">0 / 0</span>
        </div>
        <div id="blm-link-check-progress" style="display:none;">
            <label>Links Checked:</label>
            <progress id="blm-links-progress" value="0" max="100"></progress>
            <span id="blm-links-progress-text">0 / 0</span>
        </div>
    </div>

    <div id="blm-results">
        <h2>Scanned Links</h2>
        <div id="blm-filters">
            <label>
                <input type="checkbox" checked="true" id="blm-show-errors-only"> Show Errors Only
            </label>
        </div>
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
                    <th>Page</th>
                    <th>URL of link</th>
                    <th>Content of 'a' tag</th>
                    <th>Status Code</th>
                </tr>
            </thead>
            <tbody id="blm-results-body">
                <!-- Results will be populated here via JavaScript -->
            </tbody>
        </table>
        <div id="blm-pagination">
            <!-- Pagination will be added here via JavaScript -->
        </div>
    </div>

    <div id="blm-logs">
        <h2>Logs</h2>
        <textarea id="blm-log-display" rows="10" readonly></textarea>
    </div>
</div>