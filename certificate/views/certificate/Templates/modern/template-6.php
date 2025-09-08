<?php
include_once '../../../../../../../wp-load.php';
$assets_url = plugins_url( 'views/certificate/Templates/assets', COSCHOOL_CERTIFICATE );
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="<?php echo $assets_url; ?>/css/certificate.css">
	<link rel="stylesheet" href="<?php echo $assets_url; ?>/css/modern.css">
	<link rel="stylesheet" href="<?php echo $assets_url; ?>/css/template-6.css">
</head>
<body style="margin: 0;">
	<div id="certificate-wrapper" style="background-image: url('<?php echo $assets_url; ?>/img/template-6.png');">
		<div class="certificate-content-panel">
			<div class="certificate-content-header">
				<h1 class="certificate-title">CERTIFICATE</h1>
				<h4 class="certificate-description">OF APPRECIATION</h4>
			</div>
			<div class="certificate-content-body">
				<h6>This certificate is award to</h6>
				<h1>Mariah Doe</h1>
				<p class="recognition">in recognition of</p>
				<p>Successfully completion of digital marketing training and demonatrating great skills in practical courses.</p>
			</div>
			<div class="certificate-content-footer">
				<div class="certificate-date">
					<span></span>
					<p>Date</p>
				</div>
				<div class="certificate-signature">
					
					<p>Signature</p>
				</div>
				<div style="clear:both;"></div>
			</div>
		</div>
	</div>
</body>
</html>