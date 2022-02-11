<?php get_header(); ?>


<?php
require_once "wp-load.php";
global $wpdb;

//Update Code into Database
$code = $_GET['code'];
if(!empty($code)){
  $wpdb->update('wpdq_fullscript', 
  array(
    'code' => $code,
  ),
  array(
    'id' => 1
  )
  );
}


//Get Values from Database
foreach( $wpdb->get_results("SELECT * FROM wpdq_fullscript WHERE 1;") as $key => $row) {
  $current_code = $row->code;
  $client_id = $row->client_id;
  $client_secret = $row -> client_secret;
  $redirect_uri = $row -> redirect_uri;
  $access_token_d = $row -> access_token;
  $refresh_token_d = $row -> refresh_token;
}

//echo "Code:". $current_code ."\r\n";
//echo "Client ID:". $client_id. "\r\n";
//echo "Client Secret:". $client_secret . "\r\n";
//echo "Redirect URL:". $redirect_uri . "\r\n";
//echo "Access Token:". $access_token_d . "\r\n";
//echo "Refresh Token:". $refresh_token_d . "\r\n";




$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api-us-snd.fullscript.io/api/oauth/token',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
  "grant_type": "authorization_code",
  "client_id": "'.$client_id.'",
  "client_secret": "'.$client_secret.'",
  "code": "'.$current_code.'",
  "redirect_uri": "'.$redirect_uri.'"
}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
  ),
));

$response_accesstoken = curl_exec($curl);
curl_close($curl);
//echo $response_accesstoken;

$json_accesstoken = json_decode($response_accesstoken, true);
foreach($json_accesstoken as $tokens) {
  $access_token = $tokens['access_token'];
  $refresh_token = $tokens['refresh_token'];
}



if( strlen($access_token) > 10 || strlen($refresh_token) > 10 ){
  $wpdb->update('wpdq_fullscript', 
  array(
    'access_token' => $access_token,
    'refresh_token' => $refresh_token,
  ),
  array(
    'id' => 1
  )
  );
}



//Get Products
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api-us-snd.fullscript.io/api/catalog/products',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: Bearer ' .$access_token_d
  ),
));

$response_productlisting = curl_exec($curl);

curl_close($curl);
//echo $response_productlisting;
$json_productlisting = json_decode($response_productlisting, true);

$access_token_expired = $json_productlisting['error'];

//For Refreshing Token
if($access_token_expired = "The access token expired"){
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api-us-snd.fullscript.io/api/oauth/token',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>'{
    "grant_type": "refresh_token",
    "client_id": "'.$client_id.'",
    "client_secret": "'.$client_secret.'",
    "refresh_token": "'.$refresh_token_d.'",
    "redirect_uri": "'.$redirect_uri.'"
  }',
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json'
    ),
  ));
  
  $response_refreshtoken = curl_exec($curl);
  curl_close($curl);
  
  //echo $response_refreshtoken;
  
  $json_refreshtoken = json_decode($response_refreshtoken, true);   
  foreach($json_refreshtoken as $new_tokens) {
    $new_accesstoken = $new_tokens['access_token'];
    $new_refreshtoken = $new_tokens['refresh_token'];
  }


  if( strlen($new_accesstoken) > 10 || strlen($new_refreshtoken) > 10 ){
    $wpdb->update('wpdq_fullscript', 
    array(
      'access_token' => $new_accesstoken,
      'refresh_token' => $new_refreshtoken,
    ),
    array(
      'id' => 1
    )
    );
  }

}


?>

<section class="feature-products">
  <div class="container">
    <div class="row">
      <?php 
      foreach($json_productlisting as $products):
        foreach($products as $product):
          $name = $product['name']; 
          $product_image = $product['primary_variant']['image_url_large'];
          $product_price = $product['primary_variant']['msrp'];
          ?>

          <div class="col-md-3 col-sm-6">
            <div class="product-grid">
                <div class="product-image">
                    <a href="#" class="image" style="background-color:#F3F3F3;">
                        <img class="pic-1" src="<?php echo $product_image; ?>">
                    </a>
                    <a class="add-to-cart" href=""> + </a>
                </div>
                <div class="product-content">
                    <h3 class="title"><a href="#"><?php echo $name; ?></a></h3>
                     <div class="price">$<?php echo $product_price; ?></div>
                </div>

                <div class="action-buttons">
                    <a class="btn-outline">Buy Now</a>
                </div>
            </div>
          </div>


          <?php
        endforeach;
      endforeach
      ?>
    </div>  
  </div>
</section>

<?php get_footer(); ?>
