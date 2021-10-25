<?php
defined('MOODLE_INTERNAL') || die();

function siyavula_get_user_token($siyavula_config, $client_ip){
    
    $data = array(
        'name' => $siyavula_config->client_name,
        'password' => $siyavula_config->client_password,
        'theme' => 'responsive',
        'region' => $siyavula_config->client_region,
        'curriculum' => $siyavula_config->client_curriculum,
        'client_ip' => $client_ip
    );
    
    $payload = json_encode($data);

    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => $siyavula_config->url_base."api/siyavula/v1/get-token",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $payload,
    ));
    
    $response = curl_exec($curl);
    $response = json_decode($response);
    
    curl_close($curl);
    return $response->token;
}

function siyavula_get_external_user_token($siyavula_config, $client_ip, $token, $userid = 0){
    global $USER;

    $curl = curl_init();
    
    //Check verify user exitis in siyav
    if($userid == 0) {
        $email = $USER->email;
    }
    else {
        $user = core_user::get_user($userid);
        $email = $user->email;
    }
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => $siyavula_config->url_base."api/siyavula/v1/user/".$email.'/token',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array('JWT: '.$token),
    ));
    
    $response = curl_exec($curl);
    $response = json_decode($response);
    
    curl_close($curl);

    if(isset($response->errors)){
        return siyavula_create_user($siyavula_config, $token);
    }else{
        return $response;
    }
}

function siyavula_create_user($siyavula_config, $token){

    global $USER;

    $data = array(
        'external_user_id' => $USER->email,
        "role" => "Learner",
        "name" => $USER->firstname,
        "surname" => $USER->lastname,
        "password" => "123456",
        "grade" => isset($USER->profile['grade']) ? $USER->profile['Grade'] : 1,
        "country" => $USER->country != '' ? $USER->country : $siyavula_config->client_region,
        "curriculum" => isset($USER->profile['curriculum']) ? $USER->profile['Grade'] : $siyavula_config->client_curriculum,
        'email' => $USER->email,
        'dialling_code' => '27',
        'telephone' =>  $USER->phone1
    );
    
    $payload = json_encode($data);

    $curl = curl_init();
  
    curl_setopt_array($curl, array(
      CURLOPT_URL => $siyavula_config->url_base."api/siyavula/v1/user",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $payload,
      CURLOPT_HTTPHEADER => array('JWT: '.$token),
    ));

    $response = curl_exec($curl);
    $response = json_decode($response);
    curl_close($curl);
    return $response;
}