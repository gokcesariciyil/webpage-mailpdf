<?php
/**
 *
 * @package      WebPageMailPdf
 * @since        1.0.0
 * @link         https://github.com/gokcesariciyil/webpage-mailpdf
 * @author       Gökçe SARIÇİYİL <gsrcyl@gmail.com>
 * @copyright    Copyright (c) 2022, Gökçe SARIÇİYİL
 * @license      https://github.com/gokcesariciyil/webpage-mailpdf/blob/main/LICENSE MIT License
 *
 */
header('Content-Type: application/json; charset=utf-8');

require 'vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;

/*
* Configurations
* */
$config = [
  'smtp' => [
      'name' => 'WRITEYOURSERVICENAME',
      'email' => 'WRITEYOURGMAILADDRESS@gmail.com',
      'pass' => 'WRITEYOUREMAILPASSWORD'
  ]
];

/*
* Validations
* ********************************
* Required Parameters : id, email
* Required Method : GET
* */
if(!isset($_GET['id'])) {
    echo json_encode([
      'error' => true,
      'info'  => 'ID Parameter Missing'
    ]);
    return;
}

$_GET['id'] = intval($_GET['id']);

if(strlen($_GET['id']) < 5) {
    echo json_encode([
      'error' => true,
      'info'  => 'Wrong ID Parameter'
    ]);
    return;
}

if(!isset($_GET['email'])) {
    echo json_encode([
      'error' => true,
      'info'  => "E-Mail Parameter Missing"
    ]);
    return;
}

if (! filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
      'error' => true,
      'info'  => "Invalid E-Mail Address"
    ]);
    return;
}

/*
* Fetching Proxy
* Page fetched from browser simulator to dodge obstacles
* */
$webpage = file_get_contents('http://localhost/webpage-proxy/?id='.$_GET['id']);

/*
* Error message if the page is below the expected number of characters
* */
if(strlen($webpage) < 1000) {
    echo json_encode([
      'error' => true,
      'info'  => 'There is a problem with the data source. It may be blocked.'
    ]);
    return;
}

/*
* DomPDF library initialized
* */
$dompdf = new Dompdf();

/*
* Load page from proxy to dompdf
* */
$dompdf->loadHtml($webpage);

/*
* Style edits for PDF
* */
$options = $dompdf->getOptions();
$options->setDefaultFont('Roboto Flex');
$options->setIsRemoteEnabled(true);
$options->setIsFontSubsettingEnabled(false);
$dompdf->setOptions($options);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A3', 'portrait');

// Render the HTML as PDF
$dompdf->render();

/*
* SMTP Sender
* *************************
* Send e-mail the PDF file created in DomPDF
* */
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Mailer = "smtp";
$mail->SMTPDebug  = 0;  
$mail->SMTPAuth   = TRUE;
$mail->SMTPSecure = "tls";
$mail->Port       = 587;
$mail->Host       = "smtp.gmail.com";
$mail->Username   = $config['smtp']['email'];
$mail->Password   = $config['smtp']['pass'];
$mail->IsHTML(true);
$mail->SetFrom($config['smtp']['email'], $config['smtp']['name']);

$mail->AddAddress($_GET['email']);

$mail->addStringAttachment($dompdf->output(), 'webpage.pdf');

$mail->Subject = "Webpage PDF | Ad Number : ". $_GET['id'];
$content = "Ad #{$_GET['id']}'s web page view has been attached to this e-mail as a pdf via the API prepared by Gökçe Sarıçiyil.";

$mail->MsgHTML($content);

/*
* Check email sending errors
* */
if(!$mail->Send()) {
    echo json_encode([
        'error' => true,
        'info'  => "There was a problem sending the e-mail"
    ]);
    return;
} else {
    echo json_encode([
        'info'  => "The relevant advertisement has been sent to your e-mail address as a pdf."
    ]);
    return;
}

echo json_encode([
  'error' => true,
  'info'  => 'Unknown Error'
]);
return;
