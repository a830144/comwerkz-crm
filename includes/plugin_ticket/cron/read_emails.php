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


$autoreply_queue = array();

//set_time_limit(10);

// find all the mail setting accounts to check.
foreach(module_ticket::get_accounts() as $account){


    $updated_tickets = module_ticket::import_email($account['ticket_account_id']);
    if(is_array($updated_tickets)){
        $autoreply_queue = array_merge($autoreply_queue,$updated_tickets);
    }

}
imap_errors();

//print_r($autoreply_queue);

module_cache::clear('ticket');
foreach($autoreply_queue as $ticket_id){


    ob_start();
    handle_hook('ticket_sidebar',$ticket_id); // to get envato hook working quicker
    ob_end_clean();

    // we have to send the email to admin notifying them about this ticket too.
    // if this latest email came from an admin user (ie: the user is replying to a customer via email)
    // then we don't send_admin_alert or autoreply, we just send reply back to customer.
    $ticket_data = module_ticket::get_ticket($ticket_id);
    $last_ticket_message = module_ticket::get_ticket_message($ticket_data['last_ticket_message_id']);
    $admins_rel = module_ticket::get_ticket_staff_rel();
    // if the last email was from admin, send customer alert.
    if(isset($admins_rel[$last_ticket_message['from_user_id']])){
//        echo "sending a customer alert ";
//        print_r($last_ticket_message);
        module_ticket::send_customer_alert($ticket_id);
    }else{
        // last email must have been from a customer
        // alert the admin to it, and send an auto reply if the message is the first.
        module_ticket::send_admin_alert($ticket_id);
        //echo "Sent an alert to admin... sending autoreply...";
        //print_r($ticket_data);
        //echo "<br><br>";
        if(module_config::c('ticket_autoreply_every_message',0) || $ticket_data['message_count']<=1){
            module_ticket::send_autoreply($ticket_id);
        }
    }


}