<?php

require __DIR__ . '/config.php';
require __dir__ . '/util.php';
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;

try {
  // ### Payer
  // A resource representing a Payer that funds a payment
  // For paypal account payments, set payment method
  // to 'paypal'.
  $payer = new Payer();
  $payer->setPaymentMethod("paypal");

  $total = 0;

  // Set up the main registration item
  if($_POST['regtype'] == 'regular') {
    $item_name = 'Regular Registration, With Excursion';
    $item_price = $PRICES['regtype']['regular'];
  } else if($_POST['regtype'] == 'student') {
    $item_name = 'Student Registration, With Excursion';
    $item_price = $PRICES['regtype']['student'];
  } else if($_POST['regtype'] == 'regular_noexcursion') {
    $item_name = 'Regular Registration, No Excursion';
    $item_price = $PRICES['regtype']['regular_noexcursion'];
  } else if($_POST['regtype'] == 'student_noexcursion') {
    $item_name = 'Student Registration, No Excursion';
    $item_price = $PRICES['regtype']['student_noexcursion'];
  } else {
    echo 'Invalid registration type';
    exit(1);
  }

  // Update prices and names for late/early registration
  if($IS_LATE) {
    $item_name = $item_name . ', Late';
    $item_price += $LATE_PENALTY;
  } else{
    $item_name = $item_name . ', Early';
  }

  // Create the registration item
  $item_reg = new Item();
  $item_reg->setName($item_name)
    ->setCurrency('USD')
    ->setQuantity(1)
    ->setPrice($item_price);

  $total += $item_price;

  // Create the items array
  $items = array($item_reg);

  $extra_proceedings = intval($_POST['extra_proceedings']);
  $extra_tour = intval($_POST['extra_tour']);
  $extra_pages = intval($_POST['extra_pages']);

  if($_POST['author'] != 'paper') {
    $extra_pages = 0;
  }

  if($extra_proceedings > 0) {
    $item = new Item();
    $item->setName('Extra Proceedings')
      ->setCurrency('USD')
      ->setQuantity($extra_proceedings)
      ->setPrice($PRICES['extra_proceedings']);
    array_push($items, $item);
    $total += $PRICES['extra_proceedings'] * $extra_proceedings;
  }

  if($extra_tour > 0) {
    $item = new Item();
    $item->setName('Extra Tour Ticket')
      ->setCurrency('USD')
      ->setQuantity($extra_tour)
      ->setPrice($PRICES['extra_tour']);
    array_push($items, $item);
    $total += $PRICES['extra_tour'] * $extra_tour;
  }

  if($extra_pages > 0) {
    $item = new Item();
    $item->setName('Extra Pages')
      ->setCurrency('USD')
      ->setQuantity($extra_pages)
      ->setPrice($PRICES['extra_pages']);
    array_push($items, $item);
    $total += $PRICES['extra_pages'] * $extra_pages;
  }

  $itemList = new ItemList();
  $itemList->setItems($items);

  $details = new Details();
  $details->setShipping(0)->setTax(0);

  $amount = new Amount();
  $amount->setCurrency('USD')
  	->setTotal($total)
  	->setDetails($details);

  $transaction = new Transaction();
  $transaction->setAmount($amount)
  	->setItemList($itemList)
  	->setDescription('Registration for NPC 2015');

  $redirectUrls = new RedirectUrls();
  $redirectUrls->setReturnUrl("http://npc15.cs.umass.edu/registration/complete.php?success=true")
  	->setCancelUrl("http://npc15.cs.umass.edu/registration/complete.php?success=false");

  $payment = new Payment();
  $payment->setIntent("sale")
  	->setPayer($payer)
  	->setRedirectUrls($redirectUrls)
  	->setTransactions(array($transaction));

  $payment->create($apiContext);
  
} catch(Exception $e) {
  print_error('There was an error creating a PayPal payment for your registration: <pre>' . $e->getMessage() . '</pre>');
  exit(1);
}

foreach($payment->getLinks() as $link) {
	if($link->getRel() == 'approval_url') {
		$redirectUrl = $link->getHref();
		break;
	}
}

$payment_id = $payment->getId();

try {
  $db = new PDO($db_str, $db_user, $db_pass);
} catch(Exception $e) {
  print_error('Unable to connect to registration database.');
  exit(1);
}

if($db->query('
    CREATE TABLE IF NOT EXISTS
    registrations (
      id INT PRIMARY KEY AUTO_INCREMENT,
      name TEXT,
      email VARCHAR(255),
      organization TEXT,
      country TEXT,
      regtype ENUM(\'regular\', \'student\', \'regular_noexcursion\', \'student_noexcursion\'),
      author ENUM(\'paper\', \'poster\', \'participant\'),
      extra_proceedings INT,
      extra_tour INT,
      manuscript_id VARCHAR(255),
      paper_title TEXT,
      total_pages INT,
      extra_pages INT,
      vegetarian BOOL,
      cost DECIMAL(5, 2),
      payment_id VARCHAR(255),
      paid BOOL,
      free_code BOOL
    )') == FALSE) {
  $error_info = $db->errorInfo();
  print_error('Error while configuring registration database. <pre>'.$error_info[2].'</pre>');
  exit(1);
}

$stmt = $db->prepare('
  INSERT INTO registrations (
    name,
    email,  
    organization,
    country,
    regtype,
    author,
    extra_proceedings,
    extra_tour,
    manuscript_id,
    paper_title,
    total_pages,
    extra_pages,
    vegetarian,
    cost,
    payment_id,
    paid,
    free_code
  ) VALUES (
    :name,
    :email,
    :organization,
    :country,
    :regtype,
    :author,
    :extra_proceedings,
    :extra_tour,
    :manuscript_id,
    :paper_title,
    :total_pages,
    :extra_pages,
    :vegetarian,
    :cost,
    :payment_id,
    :paid,
    :free_code
  )'
);

$vegetarian = $_POST['vegetarian'] == 'yes' ? 1 : 0;
$paid = 0;
$free = 0;

if($_POST['secret_code'] == $SECRET_CODE) {
  $paid = 1;
  $free = 1;
}

$stmt->bindParam(':name', $_POST['fullname']);
$stmt->bindParam(':email', $_POST['email']);
$stmt->bindParam(':organization', $_POST['organization']);
$stmt->bindParam(':country', $_POST['country']);
$stmt->bindParam(':regtype', $_POST['regtype']);
$stmt->bindParam(':author', $_POST['author']);
$stmt->bindParam(':extra_proceedings', intval($_POST['extra_proceedings']));
$stmt->bindParam(':extra_tour', intval($_POST['extra_tour']));
$stmt->bindParam(':manuscript_id', $_POST['paper_id']);
$stmt->bindParam(':paper_title', $_POST['paper_title']);
$stmt->bindParam(':total_pages', intval($_POST['total_pages']));
$stmt->bindParam(':extra_pages', intval($_POST['extra_pages']));
$stmt->bindParam(':vegetarian', $vegetarian);
$stmt->bindParam(':cost', $total);
$stmt->bindParam(':payment_id', $payment_id);
$stmt->bindParam(':paid', $paid);
$stmt->bindParam(':free_code', $free);

if($stmt->execute() == FALSE) {
  $error_info = $stmt->errorInfo();
  print_error('Error adding registration to database. <pre>'.$error_info[2].'</pre>');
  exit(1);
}

if($free == 1) {
  echo '<h3>Registration Complete</h3>';
  echo '<p>Your free registration is now complete.</p>';
} else {
  echo '<h3>Redirecting to payment...</h3><script>document.location = "'.$redirectUrl.'";</script>';
  echo '<p>If you are not redirected to PayPal, click <a href="'.$redirectUrl.'">here</a> to continue.</p>';
}

exit();

?>
