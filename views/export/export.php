<?php
use Codexpert\Plugin\Table;

use WpPluginHub\AdnSms\AdnSmsNotification;

$smsNotification  = new AdnSmsNotification();
$rsponse          = $smsNotification->checkBalance();
$data             = json_decode($rsponse, true);

// Fixed the issue here
$balance          = isset($data['balance']['sms']) ? $data['balance']['sms'] : "";



?>

<div class="wph-row">
  <div class="wph-label-wrap">
    <label for="rm-event-name">Event Name :</label>
  </div>
  <div class="wph-field-wrap">
    <input type="text" id="rm-event-name" name="rm_event_name" class="regular-text" placeholder="DMHM2025" />
    <p class="wph-desc">Enter the event name </p>
  <button id="run-manager-event-button" class="button button-hero button-primary">
    <i class="bi bi-download"></i> Save
  </button>
  </div>
</div>


<div class="wph-row  ">
  <div class="wph-label-wrap">
    <label >Export Order Data To excel :</label>
  </div>
  <div class="wph-field-wrap ">
    <button id="run-manager-export-button" class="button button-hero button-primary">
    <i class="bi bi-download"></i>Export
  </button>
    <p class="wph-desc">This will export your completed order data to excel.</p>
  </div>
</div>


<div class="wph-row">
  <div class="wph-label-wrap">
    <label >Get Tshirt chart :</label>
  </div>
  <div class="wph-field-wrap ">
    <button id="run-manager-tshirt-chart" class="button button-hero button-primary ">
    <i class="bi bi-download"></i>Download
  </button>
    <p class="wph-desc">This will download your size chart or order</p>
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
    
<?php display_product_sales_count(); ?>
  </div>
</div>

