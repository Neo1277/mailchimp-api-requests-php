<?php
    require_once("credentials.php");
    require_once("MailchimpAPI.php");

    $mailchimpAPI = new MailchimpAPI($API_KEY);
    
    $data = array(
        'name'    => 'name3',
        'contact'   => array(
          'company'   => 'company name',
          'address1'  => 'calle 15',
          'city'    => 'Your City',
          'state'   => 'CO',
          'zip'   => 'zipcode',
          'country' => 'Colombia'
        ),
        'permission_reminder' => 'permission reminder message',
        'campaign_defaults' => array(
          'from_name'   => 'name from',
          'from_email'  => 'correo@hotmail.com',
          'subject'     => '',
          'language'    => 'en'
        ),
        'email_type_option' => true
    );
    $result0 = $mailchimpAPI->createList($data);
    //echo $result0;
    $mailchimpAPI->getLists();

    $contacts = array (
        array(
            "email_address" =>  "example@dominio.com",
            "name"          =>  "Nombres",
            "last_name"     =>  "apellidos"
        ),
        array(
            "email_address" =>  "example1@dominio.com",
            "name"          =>  "Nombres1",
            "last_name"     =>  "apellidos1"
        )
    );

    $listId = "339c24fe44";

    $finalData = $mailchimpAPI->setContactsList($contacts, $listId);
    
    $mailchimpAPI->sendContactsList($finalData);

    
    $result1 = $mailchimpAPI->deleteList($listId);
    echo $result1["message"];
?>