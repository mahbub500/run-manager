<?php
use Codexpert\Plugin\Table;
use WpPluginHub\AdnSms\AdnSmsNotification;
// get_woocommerce_product_sales();
$smsNotification = new AdnSmsNotification();
$balance = $smsNotification->checkBalance();
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
    <label >Get Tshirt chart :</label>
  </div>
  <div class="wph-field-wrap ">
    <button id="run-manager-tshirt-chart" class="button button-hero button-primary ">
    <i class="bi bi-download"></i>Downloade
  </button>
    <p class="wph-desc">This will downloade your size chart or order</p>
  </div>
</div>
<div class="wph-row">
  <p>Balance in ADN live api <strong><?php echo $balance; ?> </strong> </p>
</div>