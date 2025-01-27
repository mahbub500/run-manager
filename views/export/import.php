<?php
use Codexpert\Plugin\Table;
?>

<div class="wph-row  ">
  <div class="wph-label-wrap">
    <label >Import Order Data To excel :</label>
  </div>
  <div class="wph-field-wrap ">
    <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls">
    <button id="run-manager-import-button" class="button button-hero button-primary ">
    <i class="bi bi-download"></i>Import
  </button>
    <p class="wph-desc">This will import your excel data to order meta.</p>
  </div>
</div>