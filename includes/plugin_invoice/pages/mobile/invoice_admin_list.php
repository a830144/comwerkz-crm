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
if(!$invoice_safe)die('failed');

$search = (isset($_REQUEST['search']) && is_array($_REQUEST['search'])) ? $_REQUEST['search'] : array();
if(isset($_REQUEST['customer_id'])){
    $search['customer_id'] = $_REQUEST['customer_id'];
}
$invoices = module_invoice::get_invoices($search);

?>

<h2>
    <?php if(module_invoice::can_i('create','Invoices')){ ?>
	<span class="button">
		<?php echo create_link("New Manual Invoice","add",module_invoice::link_open('new')); ?>
	</span>
    <?php } ?>
	<?php echo _l('Invoices'); ?>
</h2>

<form action="" method="post">


<table class="search_bar" width="100%">
	<tr>
		<th width="70"><?php _e('Filter By:'); ?></th>
		<td width="180">
			<?php echo _l('Invoice Number:');?>
		</td>
		<td>
			<input type="text" name="search[generic]" value="<?php echo isset($search['generic'])?htmlspecialchars($search['generic']):''; ?>" size="30">
		</td>
		<td width="30">
			<?php echo _l('Status:');?>
		</td>
		<td>
			<?php echo print_select_box(module_invoice::get_statuses(),'search[status]',isset($search['status'])?$search['status']:''); ?>
		</td>
		<td class="search_action">
			<?php echo create_link("Reset","reset",module_invoice::link_open(false)); ?>
			<?php echo create_link("Search","submit"); ?>
		</td>
	</tr>
</table>

<?php
$pagination = process_pagination($invoices,20,0,'invoices');
$colspan = 4;
?>

<?php echo $pagination['summary'];?>

<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
	<thead>
	<tr class="title">
		<th id="invoice_number"><?php echo _l('Invoice Number'); ?></th>
		<th id="invoice_paid_date"><?php echo _l('Paid Date'); ?></th>
		<th id="invoice_website"><?php echo _l(module_config::c('project_name_single','Website')); ?></th>
        <?php if(!isset($_REQUEST['customer_id'])){ ?>
		<th id="invoice_customer"><?php echo _l('Customer'); ?></th>
        <?php } ?>
		<th id="invoice_total"><?php echo _l('Invoice Total'); ?></th>
    </tr>
    </thead>
    <tbody>
		<?php
		$c=0;
		foreach($pagination['rows'] as $invoice){
            $invoice = module_invoice::get_invoice($invoice['invoice_id']);
            ?>
            <tr class="<?php echo ($c++%2)?"odd":"even"; ?>">
                <td class="row_action">
                    <?php echo module_invoice::link_open($invoice['invoice_id'],true);?>
                </td>
                <td>
                    <?php if($invoice['date_paid'] && $invoice['date_paid'] != '0000-00-00'){ ?>
                        <?php echo print_date($invoice['date_paid']);?>
                    <?php }else if(($invoice['date_due'] && $invoice['date_due']!='0000-00-00') && (!$invoice['date_paid'] || $invoice['date_paid'] == '0000-00-00') && strtotime($invoice['date_due']) < time()){ ?>
                        <span class="error_text" style="font-weight: bold; text-decoration: underline;"><?php _e('Overdue');?></span>
                    <?php }else{ ?>
                        <span class="error_text"><?php _e('Not paid');?></span>
                    <?php } ?>
                </td>
                <td>
                    <?php
                    foreach($invoice['website_ids'] as $website_id){
                        if((int)$website_id>0){
                            echo module_website::link_open($website_id,true);
                            echo '<br/>';
                        }
                    }
                    ?>
                </td>
                <?php if(!isset($_REQUEST['customer_id'])){ ?>
                <td>
                    <?php echo module_customer::link_open($invoice['customer_id'],true);?>
                </td>
                <?php } ?>
                <td>
                    <?php echo dollar($invoice['total_amount'],true,$invoice['currency_id']);?>
                </td>
            </tr>
		<?php } ?>
	</tbody>
</table>
    <?php echo $pagination['links'];?>
</form>