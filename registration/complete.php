---
layout: page
title: Registration (test)
---

<?php

require __DIR__ . '/config.php';
require __DIR__ . '/util.php';

use PayPal\Api\ExecutePayment;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;

if(!isset($_GET['success']) || $_GET['success'] != 'true') {
  echo '<p class="alert alert-info">Your payment has been canceled.</p>';
  exit(0);
}
  
if(!isset($_GET['paymentId']) || !isset($_GET['token']) || !isset($_GET['PayerID'])) {
  print_error('Invalid response from PayPal approval.');
  exit(1);
}

$payment_id = $_GET['paymentId'];
$token = $_GET['token'];
$payer_id = $_GET['PayerID'];

$payment = Payment::get($payment_id, $apiContext);

$execution = new PaymentExecution();
$execution->setPayerId($payer_id);

try {
  $result = $payment->execute($execution, $apiContext);
} catch(Exception $e) {
  print_error('Failed to execute your PayPal payment.');
  exit(1);
}

try {
  // Connect to the database
  $db = new PDO($db_str, $db_user, $db_pass);
} catch(Exception $e) {
  print_error('Failed to connect to the registration database.');
  exit(1);
}

// Prepare a statement to get user information from the payment id
$get_stmt = $db->prepare('SELECT * FROM registrations WHERE payment_id=:payment_id');
$get_stmt->bindParam(':payment_id', $payment_id);

// Execute the statement
if($get_stmt->execute() == FALSE || $get_stmt->rowCount() == 0) {
  print_error('Unable to locate a matching registration for your payment.');
  exit(1);
}

// Get user info
$user_info = $get_stmt->fetch(PDO::FETCH_ASSOC);

// Prepare statement to mark the registration as paid
$stmt = $db->prepare('UPDATE registrations SET paid=1 WHERE payment_id=:payment_id');
$stmt->bindParam(':payment_id', $payment_id);

// Mark the registration as paid
if($stmt->execute() == FALSE) {
  print_error('Failed to mark your registration as paid.');
  exit(1);
}

$extra_proceedings = $user_info['extra_proceedings'];
$extra_tour = $user_info['extra_tour'];
$extra_pages = $user_info['extra_pages'];

// Print confirmation
?>
<h3>Your registration is confirmed</h3>
<p>Your registration has been saved and is paid in full. Please confirm all information below and keep this page for your records.</p>

<h4>Your Badge</h4>
<dl class="dl-horizontal">
  <dt>Name</dt>
  <dd><?php echo $user_info['name']; ?></dd>
  <dt>Email</dt>
  <dd><?php echo $user_info['email']; ?></dd>
  <dt>Organization</dt>
  <dd><?php echo $user_info['organization']; ?></dd>
  <dt>Country</dt>
  <dd><?php echo $user_info['country']; ?></dd>
  <dt>Author?</dt>
  <dd><?php echo $user_info['author'] == 'participant' ? 'No' : 'Yes'; ?></dd>
  <dt>Vegetarian?</dt>
  <dd><?php echo $user_info['vegetarian'] == 1 ? 'Yes' : 'No'; ?></dd>
</dl>

<h4>Receipt</h4>
<table class="table">
  <tr>
    <th>Item</th>
    <th>Cost</th>
    <th>Quantity</th>
    <th>Subtotal</th>
  </tr>
  <tr>
    <?php if($user_info['regtype'] == 'regular') { ?>
      <td>Regular Registration</td>
      <td><?php echo $PRICES['regtype']['regular']; ?> USD</td>
      <td>1</td>
      <td><?php echo $PRICES['regtype']['regular']; ?> USD</td>
    <?php } else if($user_info['regtype'] == 'student') { ?>
      <td>Student Registration</td>
      <td><?php echo $PRICES['regtype']['student']; ?> USD</td>
      <td>1</td>
      <td><?php echo $PRICES['regtype']['student']; ?> USD</td>
    <?php } else if($user_info['regtype'] == 'regular_noexcursion') { ?>
      <td>Regular Registration, No Excursion</td>
      <td><?php echo $PRICES['regtype']['regular_noexcursion']; ?> USD</td>
      <td>1</td>
      <td><?php echo $PRICES['regtype']['regular_noexcursion']; ?> USD</td>
    <?php } else if($user_info['regtype'] == 'student_noexcursion') { ?>
      <td>Student Registration, No Excursion</td>
      <td><?php echo $PRICES['regtype']['student_noexcursion']; ?> USD</td>
      <td>1</td>
      <td><?php echo $PRICES['regtype']['student_noexcursion']; ?> USD</td>
    <?php } else { print_error('Unknown registration type.'); exit(1); } ?>
  </tr>
  <?php if($IS_LATE) { ?>
    <tr>
      <td>Late Registration</td>
      <td><?php echo $LATE_PENALTY; ?> USD</td>
      <td>1</td>
      <td><?php echo $LATE_PENALTY; ?> USD</td>
    </tr>
  <?php } ?>
  <?php if($extra_proceedings > 0) { ?>
    <tr>
      <td>Additional Proceedings</td>
      <td><?php echo $PRICES['extra_proceedings']; ?> USD</td>
      <td><?php echo $extra_proceedings; ?></td>
      <td><?php echo $extra_proceedings * $PRICES['extra_proceedings']; ?> USD</td>
    </tr>
  <?php } ?>
  <?php if($extra_tour > 0) { ?>
    <tr>
      <td>Additional Tour Tickets</td>
      <td><?php echo $PRICES['extra_tour']; ?> USD</td>
      <td><?php echo $extra_tour; ?></td>
      <td><?php echo $extra_tour * $PRICES['extra_tour']; ?> USD</td>
    </tr>
  <?php } ?>
  <?php if($extra_pages > 0) { ?>
    <tr>
      <td>Additional Pages</td>
      <td><?php echo $PRICES['extra_pages']; ?> USD</td>
      <td><?php echo $extra_pages; ?></td>
      <td><?php echo $extra_pages * $PRICES['extra_pages']; ?> USD</td>
    </tr>
  <?php } ?>
  <tr>
    <td colspan=3 class="text-right"><b>Total</b></td>
    <td><b><?php echo $user_info['cost']; ?> USD</b></td>
</table>

<p>Please contact <a href="mailto:registration@npc15.cs.umass.edu">registration@npc15.cs.umass.edu</a> if you see any problems with your registration information.</p>
