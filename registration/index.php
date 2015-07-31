---
layout: page
title: Registration
---
<?php /*ini_set('display_errors', 1); error_reporting(E_ALL);*/ ?>
<?php require __DIR__ . '/config.php'; ?>

<?php
  $errors = array();
  if(count($_POST)) {
    $valid = true;

    if(!isset($_POST['fullname']) || strlen($_POST['fullname']) < 2) {
      $valid = false;
      $errors['fullname'] = 'Please enter your name as it should appear on your badge.';
    }

    if(!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
      $valid = false;
      $errors['email'] = 'Please enter a valid email address.';
    }

    if(!isset($_POST['organization']) || strlen($_POST['organization']) < 2) {
      $valid = false;
      $errors['organization'] = 'Please enter your school or organization.';
    }

    if(!isset($_POST['country']) || strlen($_POST['country']) < 2) {
      $valid = false;
      $errors['country'] = 'Please enter the country where your school or organization is located.';
    }

    if(!isset($_POST['regtype']) || ($_POST['regtype'] != 'regular' && $_POST['regtype'] != 'student' && $_POST['regtype'] != 'regular_noexcursion' && $_POST['regtype'] != 'student_noexcursion')) {
      $valid = false;
      $errors['regtype'] = 'Please select a registration type.';
    }

    if(!isset($_POST['author']) || ($_POST['author'] != 'paper' && $_POST['author'] != 'poster' && $_POST['author'] != 'participant')) {
      $valid = false;
      $errors['author'] = 'Please select an option.';
    }

    if(!isset($_POST['vegetarian']) || ($_POST['vegetarian'] != 'yes' && $_POST['vegetarian'] != 'no')) {
      $valid = false;
      $errors['vegetarian'] = 'Please select an option.';
    }

    // If author, validate additional options
    if(isset($_POST['author']) && ($_POST['author'] == 'paper' || $_POST['author'] == 'poster')) {
      if(!isset($_POST['paper_id']) || strlen($_POST['paper_id']) < 1) {
        $valid = false;
        $errors['paper_id'] = 'Please enter your manuscript ID.';
      }

      if(!isset($_POST['paper_title']) || strlen($_POST['paper_title']) < 2) {
        $valid = false;
        $errors['paper_title'] = 'Please enter the title of your paper or poster.';
      }

      if($_POST['author'] == 'paper' && !isset($_POST['camera_ready'])) {
        $valid = false;
        $errors['camera_ready'] = 'Please submit the camera ready package before registering for NPC';
      }
    }

    // If everything looks valid, process the payment
    if($valid) {
      require 'dopayment.php';
    }
  }

function pluralize($count, $singular, $plural) {
  if($count == 1) return $singular;
  else return $plural;
}

function num_str($count) {
  $nums = array(0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five');
  if($count >= 0 && $count < 6) {
    return $nums[$count];
  } else {
    return (string)$count;
  }
}
?>

<?php function text_field($id, $label) { global $errors; ?>
  <div class="form-group<?php if(isset($errors[$id])) echo " has-error"; ?>">
    <label for="<?php echo $id; ?>" class="col-md-3 control-label"><?php echo $label; ?></label>
    <div class="col-md-6">
      <input type="text" class="form-control" id="<?php echo $id; ?>" name="<?php echo $id; ?>" value="<?php if(isset($_POST[$id])) echo addslashes($_POST[$id]); ?>">
      <?php if(isset($errors[$id])) { ?>
        <span class="help-block"><?php echo $errors[$id]; ?></span>
      <?php } ?>
    </div>
  </div>
<?php } ?>

<?php function radio_field($id, $label, $options) { global $errors ?>
  <div class="form-group<?php if(isset($errors[$id])) echo " has-error"; ?>">
    <label for="<?php echo $id; ?>" class="col-md-3 control-label"><?php echo $label; ?></label>
    <div class="col-md-9">
      <?php foreach($options as $option_id => $option_label) { ?>
        <div class="radio">
          <label>
            <input type="radio" name="<?php echo $id; ?>" value="<?php echo $option_id; ?>" <?php if(isset($_POST[$id]) && $_POST[$id] == $option_id) echo " checked"; ?>>
            <?php echo $option_label; ?>
          </label>
        </div>
      <?php } ?>
      <?php if(isset($errors[$id])) { ?>
        <span class="help-block"><?php echo $errors[$id]; ?></span>
      <?php } ?>
    </div>
  </div>
<?php } ?>

<?php
function get_post_default($id, $default) {
  if(isset($_POST[$id])) return $_POST[$id];
  else return $default;
}
?>

<h2>Registration</h2>

<?php
$error_count = count($errors);

if($error_count > 0) { ?>
  <div class="alert alert-danger" role="alert">
    Please fix <?php echo pluralize($error_count, 'an error', 'errors'); ?> in
    <?php echo num_str($error_count); ?>
    <?php echo pluralize($error_count, 'field', 'fields'); ?> below.
  </div>
<?php } ?>

<div class="row">
  <div class="col-md-12">
    <form id="registration" class="form-horizontal" action="/registration/" method="post">
      <h3>Registration Information</h3>
      <p>Name tags will be printed using this information you enter here. You will have an opportunity to enter your full legal name during payment.</p>
      <?php text_field("fullname", "Your Name"); ?>
      <?php text_field("email", "Email"); ?>
      <?php text_field("organization", "Organization"); ?>
      <?php text_field("country", "Country"); ?>

      <h3>
        Registration Fee
        <small>
          &nbsp;&nbsp;
          <span class="text-success"><b>Early (Before July 15, 2015)</b></span>
          &nbsp;&nbsp;
          <span class="text-danger"><b>Late/Onsite (After July 15, 2015)</b></span>
        </small>
      </h3>

      <?php radio_field("regtype", "Registration Type", array(
        "regular" => "Regular (with excursion)
          &nbsp;&nbsp; <span class=\"text-success\"><b>USD ".$PRICES['regtype']['regular']." Early</b></span>
          &nbsp;&nbsp; <span class=\"text-danger\"><b>USD ".($PRICES['regtype']['regular']+$LATE_PENALTY)." Onsite</b></span>",
        "student" => "Student (with excursion)
          &nbsp;&nbsp; <span class=\"text-success\"><b>USD ".$PRICES['regtype']['student']." Early</b></span>
          &nbsp;&nbsp; <span class=\"text-danger\"><b>USD ".($PRICES['regtype']['student']+$LATE_PENALTY)." Onsite</b></span>",
        "regular_noexcursion" => "Regular (without excursion)
          &nbsp;&nbsp; <span class=\"text-success\"><b>USD ".$PRICES['regtype']['regular_noexcursion']." Early</b></span>
          &nbsp;&nbsp; <span class=\"text-danger\"><b>USD ".($PRICES['regtype']['regular_noexcursion']+$LATE_PENALTY)." Onsite</b></span>",
        "student_noexcursion" => "Student (without excursion)
          &nbsp;&nbsp; <span class=\"text-success\"><b>USD ".$PRICES['regtype']['student_noexcursion']." Early</b></span>
          &nbsp;&nbsp; <span class=\"text-danger\"><b>USD ".($PRICES['regtype']['student_noexcursion']+$LATE_PENALTY)." Onsite</b></span>")); ?>

      <?php radio_field("author", "Are you presenting a paper or poster?", array(
        "paper" => "Yes, a <b>paper</b>",
        "poster" => "Yes, a <b>poster</b>",
        "participant" => "No, just attending")); ?>

      <h3>Optional Items</h3>

      <div class="form-group">
        <label for="extra_proceedings" class="col-md-3 control-label">Extra Proceedings</label>
        <div class="col-md-3">
          <div class="input-group spinner">
            <div class="input-group-btn">
              <button type="button" id="extra_proceedings_down" class="btn btn-default">-</button>
            </div>
            <input type="text" class="form-control text-center" id="extra_proceedings" name="extra_proceedings" value="<?php echo get_post_default('extra_proceedings', 0); ?>">
            <div class="input-group-btn">
              <button type="button" id="extra_proceedings_up" class="btn btn-default">+</button>
            </div>
          </div>
          <p class="price">USD <?php echo $PRICES['extra_proceedings']; ?> Each</p>
        </div>
      </div>

      <div class="form-group">
        <label for="extra_tour" class="col-md-3 control-label">
          Extra Tour Tickets for September 19
        </label>
        <div class="col-md-3">
          <div class="input-group spinner">
            <div class="input-group-btn">
              <button type="button" id="extra_tour_down" class="btn btn-default">-</button>
            </div>
            <input type="text" class="form-control text-center" id="extra_tour" name="extra_tour" value="<?php echo get_post_default('extra_tour', 0); ?>">
            <div class="input-group-btn">
              <button type="button" id="extra_tour_up" class="btn btn-default">+</button>
            </div>
          </div>
          <p class="price">USD <?php echo $PRICES['extra_tour']; ?> Each</p>
        </div>
      </div>


      <div id="author_information_section" class="">
        <h3>Paper Information</h3>

        <?php text_field('paper_id', 'Manuscript ID from NPC\'15'); ?>
        <?php text_field('paper_title', 'Paper/Poster Title'); ?>

        <div id="paper_information_section">
          <div class="form-group">
            <label for="total_pages" class="col-md-3 control-label">Total Pages</label>
            <div class="col-md-3">
              <div class="input-group spinner">
                <div class="input-group-btn">
                  <button id="total_pages_down" type="button" class="btn btn-default">-</button>
                </div>
                <input type="text" class="form-control text-center" id="total_pages" name="total_pages" value="<?php echo get_post_default('total_pages', 0); ?>">
                <div class="input-group-btn">
                  <button id="total_pages_up" type="button" class="btn btn-default">+</button>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="extra_pages" class="col-md-3 control-label">Extra Pages</label>
            <div class="col-md-3">
              <div class="input-group spinner">
                <div class="input-group-btn">
                  <button type="button" id="extra_pages_down" class="btn btn-default">-</button>
                </div>
                <input type="text" class="form-control text-center" id="extra_pages" name="extra_pages" value="<?php echo get_post_default('extra_pages', 0); ?>">
                <div class="input-group-btn">
                  <button type="button" id="extra_pages_up" class="btn btn-default">+</button>
                </div>
              </div>
              <p class="price">USD <?php echo $PRICES['extra_pages']; ?> Each</p>
            </div>
          </div>

          <div class="form-group<?php if(isset($errors['camera_ready'])) echo " has-error"; ?>">
            <label class="col-md-10 col-md-offset-2">
              <input type="checkbox" name="camera_ready" id="camera_ready" value="yes">
              I have sent a final camera ready package (Source File, PDF File, and Copyright Form) to <a href="mailto:abdullah.muzahid@utsa.edu">abdullah.muzahid@utsa.edu</a>.
            </label>
            <?php if(isset($errors['camera_ready'])) { ?>
              <span class="help-block col-md-offset-2"><?php echo $errors['camera_ready']; ?></span>
            <?php } ?>
          </div>
        </div>
      </div>

      <h3>Dietary Restrictions</h3>

      <?php radio_field('vegetarian', 'Vegetarian', array('yes' => 'Yes', 'no' => 'No')); ?>

      <hr>

      <div class="form-group form-inline">
        <label for="total_cost" class="col-md-4 control-label">Total Cost</label>
        <div class="col-md-6">
          <div class="input-group">
            <div class="input-group-addon">USD</div>
            <input type="text" id="total_cost" class="form-control" value="0" readonly>
            <div class="input-group-btn">
              <button type="submit" class="btn btn-primary">Proceed to Payment</button>
            </div>
        </div>
      </div>

      <div class="col-md-12 text-center">
        <p class="help-block">
          You will be redirected to PayPal for payment. All options will be
          available for review before finalizing payment.
        </p>
      </div>

    </form>

  </div>
</div>

<script>
function updateView() {
  switch($('input[name=author]:checked', '#registration').val()) {
    case 'paper':
      $('#author_information_section').removeClass('hidden');
      $('#paper_information_section').removeClass('hidden');
      break;

    case 'poster':
      $('#author_information_section').removeClass('hidden');
      $('#paper_information_section').addClass('hidden');
      break;

    default:
      $('#author_information_section').addClass('hidden');
      $('#paper_information_section').addClass('hidden');
      break;
  }
}

$("input[name=author]", '#registration').change(updateView);

function updatePrice() {
  var total = 0;

  switch($('input[name=regtype]:checked', '#registration').val()) {
    <?php foreach($PRICES['regtype'] as $key => $val) { ?>
      case "<?php echo $key; ?>":
        total += <?php echo $val; ?>;
        <?php if($IS_LATE) { ?>
          total += <?php echo $LATE_PENALTY; ?>;
        <?php } ?>
        break;
    <?php } ?>
  }

  total += $('#extra_proceedings').val() * <?php echo $PRICES['extra_proceedings']; ?>;

  if($('input[name=author]:checked', '#registration').val() === 'paper') {
    total += $('#extra_pages').val() * <?php echo $PRICES['extra_pages']; ?>;
  }

  total += $('#extra_tour').val() * <?php echo $PRICES['extra_tour']; ?>;

  $('#total_cost').val(total);
}

$("input", '#registration').change(updatePrice);

updateView();

function makeSpinner(name) {
  var up_btn = '#'+name+'_up';
  var down_btn = '#'+name+'_down';
  var field = '#'+name;

  $(up_btn).click(function() {
    var t = parseInt($(field).val());
    $(field).val(t + 1);
    updatePrice();
  });

  $(down_btn).click(function() {
    var t = parseInt($(field).val());
    if(t > 0) {
      $(field).val(t - 1);
      updatePrice();
    }
  });
}

makeSpinner('extra_proceedings');
makeSpinner('extra_pages');
makeSpinner('extra_tour');
makeSpinner('total_pages');

$('.spinner input', '#registration').change(function() { return false; });

</script>
