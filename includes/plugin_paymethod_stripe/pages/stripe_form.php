<?php 
/** 
  * Copyright: dtbaker 2012
  * Licence: Please check CodeCanyon.net for licence details. 
  * More licence clarification available here:  http://codecanyon.net/wiki/support/legal-terms/licensing-terms/ 
  * Deploy: 11802 7369f9a61c2e79f820350e955c52cf4b
  * Envato: 31249ba5-dbc2-4eab-bc75-b0df6eb45c51
  * Package Date: 2016-12-01 15:01:26 
  * IP Address: 219.75.80.95
  */

require_once('includes/plugin_paymethod_stripe/stripe-php/lib/Stripe.php');

$stripe = array(
  "secret_key"      => module_config::c('payment_method_stripe_secret_key'),
  "publishable_key" => module_config::c('payment_method_stripe_publishable_key')
);

Stripe::setApiKey($stripe['secret_key']);

if(isset($_POST['stripeToken']) && isset($invoice_payment_data) && $invoice_payment_data['amount'] > 0){
    $token  = $_POST['stripeToken'];

    // Create the charge on Stripe's servers - this will charge the user's card
    try {
        $charge = Stripe_Charge::create(array(
          "amount" => $invoice_payment_data['amount']*100, // amount in cents, again
          "currency" => $currency_code,
          "card" => $token,
          "description" => $description)
        );
        if($charge && $charge->paid && $charge->captured){
            // successfully paid!

            update_insert("invoice_payment_id",$invoice_payment_id,"invoice_payment",array(
                                                                              'date_paid' => date('Y-m-d'),
                                                                              'amount' => $charge->amount > 0 ? $charge->amount/100 : 0,
                                                                              'method' => 'Stripe',
                                                                     ));
            module_paymethod_stripe::add_payment_data($invoice_payment_id,'log',"Successfully paid: ".var_export($charge,true));

            module_invoice::save_invoice($invoice_id, array());
            // success!
            // redirect to receipt page.
            redirect_browser(module_invoice::link_receipt($invoice_payment_id));

        }else{
            $error = "Something went wrong during strip payment. Please confirm invoice payment went through: ".htmlspecialchars($description);
            send_error($error);
            echo $error;
        }
    } catch(Stripe_CardError $e) {
      // The card has been declined
        $body = $e->getJsonBody();
          $err  = $body['error'];
        $error = "Sorry: Payment failed. <br><br>\n\n".htmlspecialchars($description).". <br><br>\n\n";
        $error .= $err['message'];
        echo $error;
        $error .= "\n\n\n".var_export($err,true);
        send_error($error);
    } catch(Exception $e){
        $body = $e->getJsonBody();
          $err  = $body['error'];
        $error = "Sorry: Payment failed. <br><br>\n\n".htmlspecialchars($description).". <br><br>\n\n";
        $error .= $err['message'];
        echo $error;
        $error .= "\n\n\n".var_export($err,true);
        send_error($error);
    }

}else if(isset($invoice_id) && $invoice_id && isset($payment_amount) && $payment_amount > 0 && isset($description)){
    ?>

    <h1><?php echo htmlspecialchars($description);?></h1>
    <form action="<?php echo full_link(_EXTERNAL_TUNNEL.'?m=paymethod_stripe&h=pay&method=stripe');?>" method="post">
        <input type="hidden" name="invoice_payment_id" value="<?php echo $invoice_payment_id;?>">
        <input type="hidden" name="invoice_id" value="<?php echo $invoice_id;?>">
      <script src="https://checkout.stripe.com/v2/checkout.js" class="stripe-button"
              data-key="<?php echo $stripe['publishable_key']; ?>"
              data-amount="<?php echo $payment_amount*100;?>"
              data-currency="<?php echo htmlspecialchars($currency_code);?>"
              <?php if(isset($user_data['email']) && strlen($user_data['email'])){ ?>
              data-email="<?php echo htmlspecialchars($user_data['email']);?>"
              <?php } ?>
              data-label="<?php _e('Pay %s by Credit Card',dollar($payment_amount,true,$invoice_payment_data['currency_id']));?>"
              data-description="<?php echo htmlspecialchars($description);?>"></script>
    </form>

    <p>&nbsp;</p>
    <p>

    <a href="<?php echo module_invoice::link_public($invoice_id);?>"><?php _e("Cancel");?></a>
    </p>
<?php }else{
    ?>
Error paying via Stripe. Please try again.
<?php
} ?>