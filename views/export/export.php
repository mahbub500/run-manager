<?php
use Codexpert\Plugin\Table;
use WpPluginHub\Run_Manager\Helper;

use WpPluginHub\AdnSms\AdnSmsNotification;

$smsNotification  = new AdnSmsNotification();
$rsponse          = $smsNotification->checkBalance();
$data             = json_decode($rsponse, true);

// Fixed the issue here
$balance	= isset($data['balance']['sms']) ? $data['balance']['sms'] : "";
$WcProduct = Helper::get_posts( [
		        'post_type'      => 'product',
		        'post_status'    => 'publish',
		        'posts_per_page' => -1,
		    ] );

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

<div class="wph-row  ">
  <div class="wph-label-wrap">
    <label >Export Order Data To excel :</label>
  </div>
  <div class="wph-field-wrap ">
    <button id="run-manager-export-button" class="button button-hero button-primary ">
    <i class="bi bi-download"></i>Export
  </button>
    <p class="wph-desc">This will export your completed order data to excel.</p>
  </div>
</div>

<div class="wph-row">
  <div class="wph-label-wrap">
    <label for="rm-select-product">Get Tshirt chart :</label>
  </div>
  <div class="wph-field-wrap">
    <!-- Dropdown populated with product -->
    <select id="rm-select-product" name="rm-select-product" class="regular-text">
      <option value="">-- Select Product --</option>
      <?php if ( ! empty( $WcProduct ) ) : ?>
        <?php foreach ( $WcProduct as $product_id => $product_title ) : ?>
          <option value="<?php echo esc_attr( $product_id ); ?>" <?php selected( $product_id ); ?>>
            <?php echo esc_html( $product_title ); ?>
          </option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>

    <!-- Download button -->
    <button id="run-manager-tshirt-chart" class="button button-hero button-primary">
      <i class="bi bi-download"></i> Download
    </button>

    <p class="wph-desc">Download your Tshirt size chart of Product</p>
  </div>
</div>

<div class="wph-row">
  <div class="wph-label-wrap">
    <label for="rm-restriction-product">Select restriction product :</label>
  </div>
  <div class="wph-field-wrap">
    <!-- Dropdown populated with product -->
    <select id="rm-restriction-product" name="product[]" multiple="multiple" class="regular-text">
    <option value="">-- Select Product --</option>
    <?php if ( ! empty( $WcProduct ) ) : ?>
      <?php foreach ( $WcProduct as $product_id => $product_title ) : ?>
        <option value="<?php echo esc_attr( $product_id ); ?>"
          <?php echo in_array( $product_id, $restricted_products ) ? 'selected' : ''; ?>>
          <?php echo esc_html( $product_title ); ?>
        </option>
      <?php endforeach; ?>
    <?php endif; ?>
  </select>

    <!-- Select button -->
    <button id="rm-product-restriction" class="button button-hero button-primary">
      <i class="bi bi-download"></i> Save data
    </button>

    <p class="wph-desc">Select your product to add restriction</p>
  </div>
</div>

<div class="wph-row">
  <div class="wph-label-wrap">
    <label>Your ADN API Remaining balance is:</label>
  </div>
  <div class="wph-field-wrap ">
    <p> <strong><?php echo $balance; ?></strong></p>
  </div>
</div>

<div class="wph-row">
  <div class="wph-label-wrap">
    <label>Product Sales count</label>
  </div>
  <div class="wph-field-wrap ">

    <select id="rm-select-main-product" name="rm_event_select_product" class="regular-text">
      <option value="">-- Select Product --</option>
      <?php if ( ! empty( $WcProduct ) ) : ?>
        <?php foreach ( $WcProduct as $product_id => $product_title ) : ?>
          <option value="<?php echo esc_attr( $product_id ); ?>" <?php selected( $product_id ); ?>>
            <?php echo esc_html( $product_title ); ?>
          </option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>

    <div class="rm-product-sales-count"></div>
    

  </div>
</div>

<?php
 // display_product_sales_count(); 
?>