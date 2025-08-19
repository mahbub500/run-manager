<?php 

use WpPluginHub\Run_Manager\Helper;
 // Save on submit
    if (isset($_POST['notification_editor_nonce']) && wp_verify_nonce($_POST['notification_editor_nonce'], 'save_notification_editor')) {

        $data = [
            'test_mode'    => isset($_POST['test_mode']) ? 1 : 0,
            'test_email'   => sanitize_email($_POST['test_email'] ?? ''),
            'test_mobile'  => sanitize_text_field($_POST['test_mobile'] ?? ''),
            'notify_email' => isset($_POST['notify_email']) ? 1 : 0,
            'notify_sms'   => isset($_POST['notify_sms']) ? 1 : 0,
            'email_content'=> wp_kses_post($_POST['email_content'] ?? ''),
            'sms_content'  => sanitize_textarea_field($_POST['sms_content'] ?? ''),
        ];

        update_option('notify_wysiwyg_data', wp_json_encode($data));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    // Load data
    $saved_data = get_option('notify_wysiwyg_data');
    $data = $saved_data ? json_decode($saved_data, true) : [];
    $placeholders = notify_placeholders(); // Get all placeholders with 


    ?>

    <div class="wrap notify-wrap">
        <h1 class="notify-title">📩 Notify With Email Or SMS</h1>
        <form method="post">
            <?php wp_nonce_field('save_notification_editor', 'notification_editor_nonce'); ?>

            <!-- Test Mode -->
            <div class="notify-option">
                <label class="notify-label">
                    <input type="checkbox" name="test_mode" id="test_mode" value="1" <?php checked(!empty($data['test_mode'])); ?>>
                    Test Mode
                </label>
                <div id="test_mode_container" class="notify-editor-wrapper" style="<?php echo !empty($data['test_mode']) ? '' : 'display:none;'; ?>">
                    <p>
                        <label>Test Email:</label><br>
                        <input type="email" id="test_email" name="test_email" value="<?php echo esc_attr($data['test_email'] ?? ''); ?>" class="regular-text">
                        <span id="test_email_msg" style="color:red; margin-left:10px; font-size:12px;">Must be a valid email.</span>
                    </p>
                    <p>
                        <label>Test Mobile:</label><br>
                        <input type="number" id="test_mobile" name="test_mobile" value="<?php echo esc_attr($data['test_mobile'] ?? ''); ?>" class="regular-text">
                        <span id="test_mobile_msg" style="color:red; margin-left:10px; font-size:12px;">Must be 11 digits.</span>
                    </p>
                </div>
            </div>

            <!-- Email Section -->
            <div class="notify-option">
                <label class="notify-label">
                    <input type="checkbox" name="notify_email" id="notify_email" value="1" <?php checked(!empty($data['notify_email'])); ?>>
                    Email Notification
                </label>
                <div id="email_editor_container" class="notify-editor-wrapper" style="<?php echo !empty($data['notify_email']) ? '' : 'display:none;'; ?>">
                    <?php
                    wp_editor(
                        $data['email_content'] ?? '',
                        'email_content',
                        [   'textarea_name' => 'email_content', 
                            'textarea_rows' => 20, 
                            'media_buttons' => false, 
                            'teeny' => true, 
                            'editor_height'  => 300  ]
                    );
                    ?>
                </div>
            </div>

            <!-- SMS Section -->
            <div class="notify-option">
                <label class="notify-label">
                    <input type="checkbox" name="notify_sms" id="notify_sms" value="1" <?php checked(!empty($data['notify_sms'])); ?>>
                    SMS Notification
                </label>
                <div id="sms_editor_container" class="notify-editor-wrapper" style="<?php echo !empty($data['notify_sms']) ? '' : 'display:none;'; ?>">
                    <?php
                    wp_editor(
                        $data['sms_content'] ?? '',
                        'sms_content',
                        [
                            'textarea_name' => 'sms_content',
                            'textarea_rows' =>20,
                            'media_buttons' => false,
                            'teeny' => true,
                            'editor_height'  => 300  
                        ]
                    );
                    ?>
                </div>
                <!-- Placeholder list -->
        
            </div>
           

        <div class="notify-placeholders">
            <strong>Available Placeholders:</strong><br>
            <?php foreach ($placeholders as $key => $desc) : ?>
                <span class="placeholder"><?php echo esc_html($key); ?></span> <?php echo esc_html($desc); ?><br>
            <?php endforeach; ?>
        </div>

            <!-- With a custom HTML button -->
            <button type="button" id="save_notify_data" class="button button-primary">💾 Save Data</button>

            <!-- Add a div for success message -->
            <div id="notify_save_msg" style="margin-top:10px;"></div>
        </form>

    </div>

    
