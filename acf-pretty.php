<?php
/**
 * Plugin Name: ACF Pretty
 * Description: Custom ACF backend field styling with configurable brand colors.
 * Version: 1.2
 * Author: Kask Creativity
 */

// ============================================================
// Default color values
// ============================================================
function acf_pretty_defaults() {
    return [
        'primary' => '#2271b1',
        'accent'  => '#135e96',
        'light'   => '#f6f7f7',
        'dark'    => '#1d2327',
        'border'  => '#dcdcde',
    ];
}

// ============================================================
// Get saved colors, falling back to defaults
// ============================================================
function acf_pretty_get_colors() {
    $defaults = acf_pretty_defaults();
    $saved    = get_option( 'acf_pretty_colors', [] );
    return wp_parse_args( $saved, $defaults );
}

// ============================================================
// Settings page
// ============================================================
add_action( 'admin_menu', function () {
    add_options_page(
        'ACF Pretty',
        'ACF Pretty',
        'manage_options',
        'acf-pretty',
        'acf_pretty_settings_page'
    );
} );

add_action( 'admin_init', function () {
    register_setting( 'acf_pretty', 'acf_pretty_colors', [
        'sanitize_callback' => 'acf_pretty_sanitize_colors',
    ] );
} );

function acf_pretty_sanitize_colors( $input ) {
    $sanitized = [];
    $keys = [ 'primary', 'accent', 'light', 'dark', 'border' ];
    foreach ( $keys as $key ) {
        $value = isset( $input[ $key ] ) ? trim( $input[ $key ] ) : '';
        $sanitized[ $key ] = preg_match( '/^#[0-9a-fA-F]{3,6}$/', $value )
            ? $value
            : acf_pretty_defaults()[ $key ];
    }
    return $sanitized;
}

function acf_pretty_settings_page() {
    $colors = acf_pretty_get_colors();
    $fields = [
        'primary' => [
            'label' => 'Primary',
            'desc'  => 'Buttons, focus rings, active tab indicators',
        ],
        'accent'  => [
            'label' => 'Accent',
            'desc'  => 'Hover and active states — usually a darker shade of Primary',
        ],
        'light'   => [
            'label' => 'Light',
            'desc'  => 'Field row hover background, table headers, tab backgrounds',
        ],
        'dark'    => [
            'label' => 'Dark',
            'desc'  => 'Metabox header background, label text color',
        ],
        'border'  => [
            'label' => 'Border',
            'desc'  => 'Input borders and row dividers',
        ],
    ];
    ?>
    <div class="wrap">
        <h1>ACF Pretty — Brand Colors</h1>
        <p style="color:#787c82;margin-bottom:24px;">
            Set your client's brand colors here. Changes apply immediately to all ACF field group screens in the admin.
        </p>

        <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Colors saved.</strong> Refresh any post edit screen to see the changes.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'acf_pretty' ); ?>

            <table class="form-table" role="presentation" style="max-width:600px;">
                <tbody>
                <?php foreach ( $fields as $key => $field ) : ?>
                    <tr>
                        <th scope="row">
                            <label for="acf_pretty_<?php echo esc_attr( $key ); ?>">
                                <?php echo esc_html( $field['label'] ); ?>
                            </label>
                        </th>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <input
                                    type="color"
                                    id="acf_pretty_<?php echo esc_attr( $key ); ?>_picker"
                                    value="<?php echo esc_attr( $colors[ $key ] ); ?>"
                                    style="width:44px;height:36px;padding:2px;border:1px solid #dcdcde;border-radius:4px;cursor:pointer;"
                                    oninput="document.getElementById('acf_pretty_<?php echo esc_attr( $key ); ?>').value = this.value; document.getElementById('preview-<?php echo esc_attr( $key ); ?>').style.background = this.value;"
                                >
                                <input
                                    type="text"
                                    id="acf_pretty_<?php echo esc_attr( $key ); ?>"
                                    name="acf_pretty_colors[<?php echo esc_attr( $key ); ?>]"
                                    value="<?php echo esc_attr( $colors[ $key ] ); ?>"
                                    maxlength="7"
                                    style="width:100px;font-family:monospace;font-size:13px;"
                                    oninput="if(/^#[0-9a-fA-F]{3,6}$/.test(this.value)){ document.getElementById('acf_pretty_<?php echo esc_attr( $key ); ?>_picker').value = this.value; document.getElementById('preview-<?php echo esc_attr( $key ); ?>').style.background = this.value; }"
                                >
                                <span style="color:#787c82;font-size:12px;">
                                    <?php echo esc_html( $field['desc'] ); ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:8px;display:flex;align-items:center;gap:16px;">
                <?php submit_button( 'Save Colors', 'primary', 'submit', false ); ?>
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=acf-pretty&reset=1' ), 'acf_pretty_reset' ) ); ?>"
                   style="color:#787c82;font-size:12px;"
                   onclick="return confirm('Reset all colors to defaults?');">
                    Reset to defaults
                </a>
            </div>
        </form>

        <hr style="margin:32px 0 24px;">
        <h2 style="font-size:14px;margin-bottom:12px;">Preview</h2>
        <div style="display:flex;gap:8px;align-items:center;">
            <?php foreach ( $fields as $key => $field ) : ?>
                <div style="text-align:center;">
                    <div id="preview-<?php echo esc_attr( $key ); ?>"
                         style="width:48px;height:48px;border-radius:6px;border:1px solid #dcdcde;background:<?php echo esc_attr( $colors[ $key ] ); ?>;margin-bottom:4px;">
                    </div>
                    <span style="font-size:11px;color:#787c82;"><?php echo esc_html( $field['label'] ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

// Handle reset
add_action( 'admin_init', function () {
    if (
        isset( $_GET['page'], $_GET['reset'] ) &&
        $_GET['page'] === 'acf-pretty' &&
        $_GET['reset'] === '1' &&
        check_admin_referer( 'acf_pretty_reset' )
    ) {
        delete_option( 'acf_pretty_colors' );
        wp_redirect( admin_url( 'options-general.php?page=acf-pretty&settings-updated=1' ) );
        exit;
    }
} );

// ============================================================
// Inject styles into admin head
// ============================================================
add_action( 'admin_head', function () {

    $screen = get_current_screen();

    if ( ! $screen || ! in_array( $screen->base, [ 'post', 'post-new' ] ) ) {
        return;
    }

    $c = acf_pretty_get_colors();

    ?>
    <style>
    :root {
        --acf-primary: <?php echo esc_attr( $c['primary'] ); ?>;
        --acf-accent:  <?php echo esc_attr( $c['accent'] ); ?>;
        --acf-light:   <?php echo esc_attr( $c['light'] ); ?>;
        --acf-dark:    <?php echo esc_attr( $c['dark'] ); ?>;
        --acf-border:  <?php echo esc_attr( $c['border'] ); ?>;
    }

    /* --- Metabox / Field Group Container --- */
    .acf-postbox {
        border: none !important;
        border-radius: 6px !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.10) !important;
        overflow: hidden;
    }

    .acf-postbox .postbox-header {
        background: var(--acf-dark);
        border-radius: 6px 6px 0 0;
        padding: 2px 12px;
    }

    .acf-postbox .postbox-header h2,
    .acf-postbox .postbox-header .hndle {
        color: #fff !important;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .acf-postbox .postbox-header .handle-actions .toggle-indicator::before {
        color: rgba(255,255,255,0.6);
    }

    /* --- Fields Wrapper --- */
    .acf-fields {
        background: #fff;
    }

    /* --- Individual Field Row --- */
    .acf-field {
        border-top: 1px solid var(--acf-border) !important;
        padding: 16px 18px !important;
        transition: background 0.15s ease;
    }

    .acf-field:first-child {
        border-top: none !important;
    }

    .acf-field:hover {
        background: var(--acf-light);
    }

    /* --- Labels --- */
    .acf-field .acf-label {
        padding-top: 2px;
    }

    .acf-field .acf-label label {
        color: var(--acf-dark);
        font-size: 13px;
        font-weight: 600;
    }

    .acf-field .acf-label .description {
        color: #787c82;
        font-size: 11.5px;
        font-style: normal;
        margin-top: 4px;
        line-height: 1.5;
    }

    .acf-field .acf-label .acf-required {
        color: #d63638;
    }

    /* --- Input Column --- */
    .acf-field .acf-input {
        padding-left: 12px;
    }

    /* --- Text Inputs --- */
    .acf-field input[type="text"],
    .acf-field input[type="number"],
    .acf-field input[type="email"],
    .acf-field input[type="url"],
    .acf-field input[type="password"] {
        border: 1px solid var(--acf-border);
        border-radius: 4px;
        padding: 7px 10px;
        font-size: 13px;
        width: 100%;
        max-width: 480px;
        box-shadow: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .acf-field input[type="text"]:focus,
    .acf-field input[type="number"]:focus,
    .acf-field input[type="email"]:focus,
    .acf-field input[type="url"]:focus {
        border-color: var(--acf-primary);
        box-shadow: 0 0 0 1px var(--acf-primary);
        outline: none;
    }

    /* --- Textarea --- */
    .acf-field textarea {
        border: 1px solid var(--acf-border);
        border-radius: 4px;
        padding: 8px 10px;
        font-size: 13px;
        width: 100%;
        max-width: 600px;
        box-shadow: none;
        resize: vertical;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .acf-field textarea:focus {
        border-color: var(--acf-primary);
        box-shadow: 0 0 0 1px var(--acf-primary);
        outline: none;
    }

    /* --- Select --- */
    .acf-field select {
        border: 1px solid var(--acf-border);
        border-radius: 4px;
        padding: 6px 28px 6px 10px;
        font-size: 13px;
        min-width: 200px;
        max-width: 480px;
        transition: border-color 0.15s ease;
    }

    .acf-field select:focus {
        border-color: var(--acf-primary);
        box-shadow: 0 0 0 1px var(--acf-primary);
        outline: none;
    }

    /* --- Media Upload Buttons --- */
    .acf-field .acf-button,
    .acf-field .button.acf-button,
    .acf-image-uploader .acf-button,
    .acf-file-uploader .acf-button,
    .acf-gallery .acf-button {
        background: var(--acf-primary) !important;
        border: none !important;
        border-radius: 4px !important;
        color: #fff !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        padding: 6px 14px !important;
        cursor: pointer !important;
        text-shadow: none !important;
        box-shadow: none !important;
        transition: background 0.15s ease !important;
    }

    .acf-field .acf-button:hover,
    .acf-field .button.acf-button:hover,
    .acf-image-uploader .acf-button:hover,
    .acf-file-uploader .acf-button:hover,
    .acf-gallery .acf-button:hover {
        background: var(--acf-accent) !important;
        color: #fff !important;
    }

    .acf-field .acf-button-delete,
    .acf-image-uploader .acf-button-delete,
    .acf-file-uploader .acf-button-delete {
        background: transparent !important;
        border: 1px solid var(--acf-border) !important;
        border-radius: 4px !important;
        color: #787c82 !important;
        font-size: 12px !important;
        padding: 5px 12px !important;
        text-shadow: none !important;
        box-shadow: none !important;
        transition: border-color 0.15s ease, color 0.15s ease !important;
    }

    .acf-field .acf-button-delete:hover,
    .acf-image-uploader .acf-button-delete:hover,
    .acf-file-uploader .acf-button-delete:hover {
        border-color: #d63638 !important;
        color: #d63638 !important;
        background: transparent !important;
    }

    .acf-image-uploader .acf-thumbnail,
    .acf-file-uploader .acf-file-icon {
        border-radius: 4px;
        border: 1px solid var(--acf-border);
    }

    .acf-image-uploader .acf-placeholder,
    .acf-file-uploader .acf-placeholder {
        border: 2px dashed var(--acf-border);
        border-radius: 4px;
        color: #787c82;
        font-size: 12px;
        padding: 12px;
    }

    /* --- Tabs --- */
    .acf-tab-wrap .acf-tab-group {
        background: var(--acf-light);
        border-bottom: 2px solid var(--acf-border);
        padding: 0 18px;
    }

    .acf-tab-wrap .acf-tab-group li a {
        color: #50575e;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 10px 14px;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
    }

    .acf-tab-wrap .acf-tab-group li.active a {
        color: var(--acf-primary);
        border-bottom-color: var(--acf-primary);
    }

    /* --- Repeater --- */
    .acf-repeater .acf-table thead th {
        background: var(--acf-light);
        color: #50575e;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--acf-border);
    }

    .acf-repeater .acf-row:not(.acf-clone):hover > td {
        background: var(--acf-light);
    }

    .acf-repeater .acf-row-handle {
        background: var(--acf-light);
    }

    .acf-repeater .acf-actions .acf-button,
    .acf-repeater > .acf-actions > .button {
        background: var(--acf-primary);
        border: none;
        border-radius: 4px;
        color: #fff;
        font-size: 12px;
        padding: 5px 14px;
        cursor: pointer;
        transition: background 0.15s;
    }

    .acf-repeater .acf-actions .acf-button:hover,
    .acf-repeater > .acf-actions > .button:hover {
        background: var(--acf-accent);
    }

    /* --- Flexible Content --- */
    .acf-flexible-content .layout .acf-fc-layout-handle {
        background: var(--acf-light);
        border-left: 3px solid var(--acf-primary);
        padding: 10px 14px;
        font-weight: 600;
        font-size: 12px;
        color: var(--acf-primary);
    }

    /* --- Checkbox / Radio --- */
    .acf-field .acf-checkbox-list li,
    .acf-field .acf-radio-list li {
        padding: 3px 0;
    }

    /* --- Message Field --- */
    .acf-field[data-type="message"] .acf-message {
        background: var(--acf-light);
        border-left: 3px solid var(--acf-primary);
        border-radius: 0 4px 4px 0;
        padding: 10px 14px;
        font-size: 13px;
        color: var(--acf-dark);
    }

    /* --- Conditional Logic --- */
    .acf-field.-collapsed {
        opacity: 0.55;
    }
    </style>
    <?php
} );
