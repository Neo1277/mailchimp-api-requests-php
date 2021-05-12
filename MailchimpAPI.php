<?php

class MailchimpAPI {
  // Properties
  public $protocol = "https://";
  public $url = 'api.mailchimp.com/3.0/';
  public $auth ;
  public $dataCenter;
  public $curlConnection;
  
  function __construct($apiKey) {
    $this->apiKey = $apiKey;
    $this->auth = base64_encode( 'user:'.$apiKey );
    $this->dataCenter = substr($apiKey,strpos($apiKey,'-')+1).".";
  }

  /**
   * Create list in Mailchimp
   * This mehotd receive one parameter: data to create list (audience)
   * Example:
   *  $data = array(
   *    'name'    => 'name3',
   *    'contact'   => array(
   *    'company'   => 'company name',
   *        'address1'  => 'calle 15',
   *        'city'    => 'Your City',
   *        'state'   => 'CO',
   *        'zip'   => 'zipcode',
   *        'country' => 'Colombia'
   *    ),
   *    'permission_reminder' => 'permission reminder message',
   *    'campaign_defaults' => array(
   *        'from_name'   => 'name from',
   *        'from_email'  => 'correo@hotmail.com',
   *        'subject'     => '',
   *        'language'    => 'en'
   *    ),
   *    'email_type_option' => true
   *  );
   */
  public function createList($data) {
    
    $data       = json_encode($data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->protocol.$this->dataCenter.$this->url.'lists/');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
        'Authorization: Basic '.$this->auth));
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $result = curl_exec($ch);
    
    //return $result;
    if($result === false) {
      $error      = true;
      $message    = 'Error Mailchimp API add_list: '. curl_error($ch);
      curl_close($ch);
    } else {
      $message    = 'List added succesfully ';
      curl_close($ch);
    }

    $response = array(
      'result'    => $result,
      'message'   => $message
    );

    return $response;
  }

  /**
   * Request to delete list (audience)
   */
  public function deleteList($listId) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->protocol.$this->dataCenter.$this->url.'lists/'.$listId);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
        'Authorization: Basic '.$this->auth));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    
    if($result === false) {
      $error      = true;
      $message    = 'Error Mailchimp API remove_list: '. curl_error($ch);
      curl_close($ch);
    } else {
      $message    = 'List removed succesfully ';
      curl_close($ch);
    }
    
    $response = array(
      'result'    => $result,
      'message'   => $message
    );

    return $response;
  }

  /**
   * This method will return a table with the lists of Mailchimp (id, name, member count)
   */
  public function getLists(){
    /*Obtener id de la lista recien creada*/
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->protocol.$this->dataCenter.$this->url.'lists/'."?count=5000");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
        'Authorization: Basic '.$this->auth));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    
    if($result === false){
      $error      = true;
      $message    = 'Error Mailchimp API id_list: '. curl_error($ch);
      echo $message;
    } else {
      
      $result = json_decode( $result );
      
      if( !empty($result->lists) ) {
        echo "Lists <br>";
        echo '<table>';

        foreach( $result->lists as $list ){
          
          echo '<tr><td>' . $list->id . '</td><td>' . $list->name . ' </td><td>' . $list->stats->member_count . '</td></tr>';
        }

        echo '</table>';

      } elseif ( is_int( $result->status ) ) { 
        // More information about errors http://developer.mailchimp.com/documentation/mailchimp/guides/error-glossary/
        $error      = true;
        $message    = 'Error Mailchimp API id_list: '. $result->title. ' - '.$result->detail;
        echo $message;
      }
    
    }

    curl_close($ch);
  }

  /**
   * Set nested array of contacts and return it
   */
  public function setContactsList($contacts, $listId) {

    foreach ($contacts as $contact) {

      // Validate email format
      if (
        !empty($contact["email_address"]) && 
        filter_var($contact["email_address"], FILTER_VALIDATE_EMAIL)
        ) {

        $data =  array(
          'apikey'        => $this->apiKey,
          'email_address' => $contact["email_address"],
          'status'        => 'subscribed',
          'merge_fields'  =>  array(
            'FNAME'   =>  utf8_encode($contact["name"]),
            'LNAME'   =>  utf8_encode($contact["last_name"])
          )
        );
        
        $jsonBodyData        = json_encode($data);
        
        $finalData['operations'][] = array(
          "method" => "POST",
          "path"   => "/lists/$listId/members/",
          "body"   => $jsonBodyData
        );
          
      }
    }

    return $finalData;
  }

  /**
   * Request to send nested array with list of contacts to Mailchimp
   */
  public function sendContactsList($finalData) {
    
    $data = json_encode($finalData);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->protocol.$this->dataCenter.$this->url.'batches/');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','charset=utf-8',
        'Authorization: Basic '.$this->auth));
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $result = curl_exec($ch);

    if($result === false)
    {
      $message    = ' Curl error: ' . curl_error($ch);
      curl_close($ch);
    }
    else
    {
      $message    = 'Contact list sent succesfully ';
      curl_close($ch);
    }
    
    $response = array(
      'result'    => $result,
      'message'   => $message
    );

    return $response;
  }

}
