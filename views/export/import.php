<?php
use Codexpert\Plugin\Table;
?>

<div class="wph-row  ">
  <div class="wph-label-wrap">
    <label >Import Order Data From excel :</label>
  </div>
  <div class="wph-field-wrap ">
    <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls">
    <button id="run-manager-import-button" class="button button-hero button-primary ">
    <i class="bi bi-download"></i>Import
  </button>
    <p class="wph-desc">This will import your excel data to order meta.</p>
  </div>


<div class="wph-label-wrap">
    <label >Upload your race data :</label>
  </div>
  <div class="wph-field-wrap ">
    <input type="file" name="race_excel_file" id="race_excel_file" accept=".xlsx, .xls">
    <button id="run-manager-upload-race-data" class="button button-hero button-primary ">
    <i class="bi bi-download"></i>Upload
  </button>
    <p class="wph-desc">Upload Only xl file, first sheet for certificate data</p>
  </div>
</div>

<div class="wph-label-wrap">
    <label >Generate Munual certificate :</label>
  </div>

  <div class="wph-field-wrap ">
    <input type="number" class="certificate-number" name="certificate-number">
    <button id="generate-munual-certificate" class="button button-hero button-primary ">
    <i class="bi bi-download"></i>Generate
  </button>
    <p class="wph-desc">First, you need to upload your data to generate a munual certificate.</p>
  </div>
</div>