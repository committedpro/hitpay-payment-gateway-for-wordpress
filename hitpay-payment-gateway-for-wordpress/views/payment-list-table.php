<?php

  global $payment_list;

?>

<div class="wrap">
  <h2>HitPay Transactions</h2>

  <div id="poststuff">
    <div id="post-body" class="metabox-holder">
      <div id="post-body-content">
        <div class="meta-box-sortables ui-sortable">
          <form method="post">
            <?php
              //$payment_list->search_box('Search', 'search_id');
              $payment_list->prepare_items();
              $payment_list->display();
            ?>
          </form>
        </div>
      </div>
    </div>
    <br class="clear">
  </div>
</div>
