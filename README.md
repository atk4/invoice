# Invoice Addons for ATK4

This add-on implements a subjective invoicing and payment module
for your applications. Here is how to use:

1. composer require atk4/invoice
2. create page in your admin interface:

```php
$app->add(new MasterCrud())->setModel(
  new Model\Client(),
  ['Invoices'=>['Payments']]
);
```

This page will now allow you to enter list of your clients. In addition to 
basic actions of "Edit" and "Delete" you'll see buttons:
 - Send Statement
 
Before statement is sent - you will see a preview.

You can also click on any client to see list of his invoices
and payments for these invoices. They will be in the separate tabs.
Adding invoice is simple. Once added invoice will have action
buttons:
 - Email invoice
 
Before email is sent you'll see a preview.

 - Credit Note
 
 Will duplicate current invoice with exact copy but
 with negative amount(s).
 
  - Refund 
  
 Will refund any payments associated with said invoice.
 
 Finally when you select a payment you should also
 see action Refund.
 
 TODO: actions are not implemented yet.
 
