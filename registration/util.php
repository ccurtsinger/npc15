<?php

function print_error($message) {
  echo '<p class="alert alert-danger">';
  echo $message;
  echo ' Please contact <a href="mailto:registration@npc15.cs.umass.edu">registration@npc15.cs.umass.edu</a> for assistance.';
  echo '</p>';
}

?>