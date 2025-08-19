<?php 
$saved_data = get_option('notify_wysiwyg_data');
$data = $saved_data ? json_decode( $saved_data, true ) : [];
?>
<div class="wrap notify-wrap">
    <h1 class="notify-title">📩 Notify</h1>
    <form id="notifyForm" method="post">

        <?php
        // Load saved data again
        $saved_data = get_option('notify_wysiwyg_data');
        $data = $saved_data ? json_decode($saved_data, true) : [];
        ?>

        <!-- ✅ Test Mode -->
        <div class="notify-option">
            <label class="notify-label">
                <input type="checkbox" name="test_mode" id="test_mode" value="1" <?php checked( !empty($data['test_mode']) ); ?>>
                <span>Test Mode</span>
            </label>
            <div id="test_mode_container" class="notify-editor" style="<?php echo empty($data['test_mode']) ? 'display:none;' : ''; ?>">
                <p>
                    <label for="test_email"><strong>Test Email:</strong></label><br>
                    <input type="email" name="test_email" id="test_email" value="<?php echo !empty($data['test_email']) ? esc_attr($data['test_email']) : ''; ?>" class="regular-text">
                </p>
                <p>
                    <label for="test_mobile"><strong>Test Mobile Number:</strong></label><br>
                    <input type="text" name="test_mobile" id="test_mobile" value="<?php echo !empty($data['test_mobile']) ? esc_attr($data['test_mobile']) : ''; ?>" class="regular-text">
                </p>
            </div>
        </div>

        <!-- ✅ Email Section -->
        <div class="notify-option">
            <label class="notify-label">
                <input type="checkbox" name="notify_email" id="notify_email" value="1" <?php checked( !empty($data['notify_email']) ); ?>>
                <span>Email Notification</span>
            </label>
            <div id="email_editor_container" class="notify-editor" style="<?php echo empty($data['notify_email']) ? 'display:none;' : ''; ?>">
                <?php
                wp_editor(
                    !empty($data['email_content']) ? $data['email_content'] : '',
                    'email_editor',
                    [
                        'textarea_name' => 'email_content',
                        'textarea_rows' => 10,
                        'media_buttons' => false,
                        'teeny' => true,
                    ]
                );
                ?>
            </div>
        </div>

        <!-- ✅ SMS Section -->
        <div class="notify-option">
            <label class="notify-label">
                <input type="checkbox" name="notify_sms" id="notify_sms" value="1" <?php checked( !empty($data['notify_sms']) ); ?>>
                <span>SMS Notification</span>
            </label>
            <div id="sms_editor_container" class="notify-editor" style="<?php echo empty($data['notify_sms']) ? 'display:none;' : ''; ?>">
                <?php
                wp_editor(
                    !empty($data['sms_content']) ? $data['sms_content'] : '',
                    'sms_editor',
                    [
                        'textarea_name' => 'sms_content',
                        'textarea_rows' => 6,
                        'media_buttons' => false,
                        'teeny' => true,
                    ]
                );
                ?>
            </div>
        </div>

        <?php submit_button('💾 Save Data'); ?>
    </form>
</div>
