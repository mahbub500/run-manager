<?php
use Codexpert\Plugin\Table;
use WpPluginHub\Run_Manager\Helper;
use WpPluginHub\AdnSms\AdnSmsNotification;

// ===== Fetch ADN Balance =====
$smsNotification = new AdnSmsNotification();
$response        = $smsNotification->checkBalance();
$data            = json_decode($response, true);
$balance         = isset($data['balance']['sms']) ? $data['balance']['sms'] : "";

// ===== Fetch All Products =====
$WcProduct = Helper::get_posts( [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
] );

// ===== Fetch Restricted Products =====
$args = [
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => [
        [
            'key'   => '_restriction_enabled',
            'value' => true,
        ]
    ]
];
$restricted_products = get_posts( $args );
?>

<!-- ===== Export Order Data ===== -->
<div class="wph-row">
  <div class="wph-label-wrap">
    <label>Export Order Data To Excel :</label>
  </div>
  <div class="wph-field-wrap">
    <button id="run-manager-export-button" class="button button-hero button-primary">
      <i class="bi bi-download"></i> Export
    </button>
    <p class="wph-desc">This will export your completed order data to Excel.</p>
  </div>
</div>

<!-- ===== T-Shirt Size Chart ===== -->
<div class="wph-row">
  <div class="wph-label-wrap">
    <label for="rm-select-product">Get T-Shirt Chart :</label>
  </div>
  <div class="wph-field-wrap">
    <select id="rm-select-product" name="rm-select-product" class="regular-text">
      <option value="">-- Select Product --</option>
      <?php foreach ( $WcProduct as $product_id => $product_title ) : ?>
        <option value="<?php echo esc_attr( $product_id ); ?>">
          <?php echo esc_html( $product_title ); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button id="run-manager-tshirt-chart" class="button button-hero button-primary">
      <i class="bi bi-download"></i> Download
    </button>

    <p class="wph-desc">Download your T-shirt size chart of a product.</p>
  </div>
</div>

<!-- ===== Restrict Products ===== -->
<div class="wph-row">
  <div class="wph-label-wrap">
    <label for="rm-restriction-product">Select Restriction Product :</label>
  </div>
  <div class="wph-field-wrap">
    <select id="rm-restriction-product" name="product[]" multiple="multiple" class="regular-text">
      <option value="">-- Select Product --</option>
      <?php foreach ( $WcProduct as $product_id => $product_title ) : ?>
        <option value="<?php echo esc_attr( $product_id ); ?>"
          <?php echo in_array( $product_id, $restricted_products ) ? 'selected' : ''; ?>>
          <?php echo esc_html( $product_title ); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button id="rm-product-restriction" class="button button-hero button-primary">
      <i class="bi bi-download"></i> Save Data
    </button>

    <p class="wph-desc">Select your product(s) to add restriction.</p>
  </div>
</div>

<!-- ===== ADN Balance ===== -->
<div class="wph-row">
  <div class="wph-label-wrap">
    <label>Your ADN API Remaining Balance:</label>
  </div>
  <div class="wph-field-wrap">
    <p><strong><?php echo esc_html( $balance ); ?></strong></p>
  </div>
</div>

<!-- ===== Product Sales Count ===== -->
<div class="wph-row">
  <div class="wph-label-wrap">
    <label for="rm-select-main-product">Product Sales Count :</label>
  </div>
  <div class="wph-field-wrap">
    <select id="rm-select-main-product" name="rm_event_select_product" class="regular-text">
      <option value="">-- Select Product --</option>
      <?php foreach ( $WcProduct as $product_id => $product_title ) : ?>
        <option value="<?php echo esc_attr( $product_id ); ?>">
          <?php echo esc_html( $product_title ); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <div class="rm-product-sales-count"></div>
  </div>
</div>
