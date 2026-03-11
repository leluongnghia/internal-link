<?php
/**
 * Provide a admin area view for the Plugin Silo Manager
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin/partials
 */
?>
<div class="wrap">
    <h1>Silo Manager</h1>
    <p>Configure Pillar and Cluster content. AIL's Silo gate ensures links obey these hierarchies.</p>

    <div class="ail-silo-container" style="margin-top:20px;">
        <p><i>Under Construction. The DB tables exist for this feature, but the UI is still being built. Stay tuned!</i>
        </p>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th>Pillar Post</th>
                    <th>Sub Pages (Cluster)</th>
                    <th>Strategy</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Placeholder row -->
                <tr>
                    <td><strong>Example Pillar: "Ultimate Guide to SEO"</strong></td>
                    <td>- On-page SEO<br>- Off-page SEO<br>- Technical SEO</td>
                    <td>Strict</td>
                    <td><button class="button">Edit</button></td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align:center; padding: 20px;">
                        <button class="button button-primary">+ Create New Silo</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>