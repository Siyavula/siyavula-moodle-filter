<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/adminlib.php');

function siyavula_get_user_token($siyavulaconfig, $clientip) {
    global $USER, $PAGE, $CFG;

    $data = array(
    'name' => $siyavulaconfig->client_name,
    'password' => $siyavulaconfig->client_password,
    'theme' => 'responsive',
    'region' => $siyavulaconfig->client_region,
    'curriculum' => $siyavulaconfig->client_curriculum,
    'client_ip' => $clientip
    );

    $apiroute  = $siyavulaconfig->url_base . "api/siyavula/v1/get-token";
    $payload = json_encode($data);

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $siyavulaconfig->url_base . "api/siyavula/v1/get-token",
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
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $response = json_decode($response);

    $name = __FUNCTION__;

    if (($siyavulaconfig->debug_enabled == 1 || $CFG->debugdisplay == 1) && $USER->id != 0) {
        siyavula_debug_message($name, $apiroute, $payload, $response, $httpcode);
    }

    curl_close($curl);
    if (isset($response->token)) {
        return $response->token;
    }
}

/**
 * Get the external user ID based on the Siyavula configuration and user profile.
 *
 * @param object $siyavulaconfig Configuration object for Siyavula.
 * @param object $user User object containing profile information.
 * @return string The external user ID, typically the user's email or a unique field.
 */
function siyavula_get_external_user_id($siyavulaconfig, $user) {

    // Load the user's custom profile fields into the $USER object.
    profile_load_data($user);

    // Retrieve the unique user field setting from the Siyavula filter configuration.
    $uniqueuserfield = get_config('filter_siyavula', 'unique_user_field');
    if (empty($uniqueuserfield)) {
        // Default to 'email' if the unique user field is not set.
        $uniqueuserfield = 'email';
    }

    return !empty($user->$uniqueuserfield) ? $user->$uniqueuserfield : $user->email;
}

/**
 * Get the external user token for a user.
 *
 * @param object $siyavulaconfig Configuration object for Siyavula.
 * @param string $clientip Client IP address.
 * @param string $token JWT token for authentication.
 * @param int $userid User ID (default is 0, which means current user).
 * @return object Response from the Siyavula API containing the user token.
 */
function siyavula_get_external_user_token($siyavulaconfig, $clientip, $token, $userid = 0, $uuid = '') {
    global $USER, $CFG;

    $curl = curl_init();

    $user = $userid == 0 ? $USER : core_user::get_user($userid);
    $externaluserid = siyavula_get_external_user_id($siyavulaconfig, $user);

    $apiroute = $siyavulaconfig->url_base . "api/siyavula/v1/user/" . $externaluserid . '/token';

    curl_setopt_array($curl, array(
        CURLOPT_URL => $siyavulaconfig->url_base . "api/siyavula/v1/user/" . $externaluserid . '/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array('JWT: ' . $token),
    ));

    $payload = $token;
    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $response = json_decode($response);

    $name = __FUNCTION__;

    if (($siyavulaconfig->debug_enabled == 1 || $CFG->debugdisplay == 1) && $USER->id != 0) {
        siyavula_debug_message($name, $apiroute, $payload, $response, $httpcode);
    }

    curl_close($curl);

    if (isset($response->errors)) {

        $newuser = siyavula_create_user($siyavulaconfig, $token);
        // Confirm that the user was created successfully.
        // If the uuid is not empty, the user has already been created and a token fetch was attempted.
        // Do not retry to avoid an infinite loop.
        if (!empty($newuser->uuid) && empty($uuid)) {
            return siyavula_get_external_user_token($siyavulaconfig, $clientip, $token, $userid, $newuser->uuid);
        }

        return false;

    } else {
        return $response;
    }
}


/**
 * Create a user in Siyavula.
 *
 * Finds the selected user unique field and uses it as the external_user_id.
 *
 * @param object $siyavulaconfig Configuration object for Siyavula.
 * @param string $token JWT token for authentication.
 * @return object Response from the Siyavula API.
 */
function siyavula_create_user($siyavulaconfig, $token) {
    global $USER, $CFG;

    // Load the user's custom profile fields into the $USER object.
    profile_load_data($USER);

    // Retrieve the unique user field setting from the Siyavula filter configuration.
    $uniqueuserfield = get_config('filter_siyavula', 'unique_user_field');
    if (empty($uniqueuserfield)) {
        // Default to 'email' if the unique user field is not set.
        $uniqueuserfield = 'email';
    }

    $data = array(
        'external_user_id' => !empty($USER->$uniqueuserfield) ? $USER->$uniqueuserfield : $USER->email,
        "role" => "Learner",
        "password" => "123456",
        "grade" => isset($USER->profile['grade']) ? $USER->profile['Grade'] : 1,
        "curriculum" => isset($USER->profile['curriculum']) ? $USER->profile['Grade'] : $siyavulaconfig->client_curriculum,
        'email' => $USER->email,
        'dialling_code' => '27',
    );

    // Prepare the personal data to be sent to the Siyavula API.
    $personalfields = get_config('filter_siyavula', 'personal_fields') ?: '';
    if (!empty($personalfields)) {
        // If personal fields are set, use them to populate the user data.
        array_map(function($field) use (&$data, $USER) {
            $data[$field] = $USER->$field ?? '';
        }, explode(',', $personalfields));
    }

    // Rename fields to match Siyavula API requirements.
    $rename = ['name' => 'firstname', 'surname' => 'lastname', 'telephone' => 'phone1'];
    foreach ($rename as $newkey => $oldkey) {
        if (isset($data[$oldkey])) {
            $data[$newkey] = $data[$oldkey];
            unset($data[$oldkey]);
        }
    }

    // Set the country field, defaulting to the configured region if not set.
    $data["country"] = ($USER->country != '' && in_array('country', $personalfields))
        ? $USER->country : '';
    if ($data['country'] == '' && $siyavulaconfig->client_region != "INTL") {
        $data['country'] = $siyavulaconfig->client_region;
    }

    $payload = json_encode($data);
    $curl = curl_init();
    $apiroute = $siyavulaconfig->url_base . "api/siyavula/v1/user";

    curl_setopt_array($curl, array(
        CURLOPT_URL => $siyavulaconfig->url_base . "api/siyavula/v1/user",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array('JWT: ' . $token),
    ));

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $response = json_decode($response);

    $name = __FUNCTION__;

    if (($siyavulaconfig->debug_enabled == 1 || $CFG->debugdisplay == 1) && $USER->id != 0) {
        siyavula_debug_message($name, $apiroute, $payload, $response, $httpcode);
    }

    curl_close($curl);
    return $response;
}

function siyavula_debug_message($namefunction, $apiroute, $payload, $response, $httpcode) {

    global $CFG;

    $id = optional_param('section', '', PARAM_TEXT);
    $clientip = $_SERVER['REMOTE_ADDR'];
    $payloadarray = json_decode($payload, true);
    $errors = '';

    $payloadname = '';
    $payloadpassword = '';
    $payloadregion = '';
    $payloadcurriculum = '';
    $payloadip = '';
    $payloadtheme = '';

    if (isset($payloadarray['name'])) {
        $payloadname         = 'Name :' . $payloadarray['name'];
    }

    if (isset($payloadarray['password'])) {
        $payloadpassword     = 'Password : ' . $payloadarray['password'];
    }

    if (isset($payloadarray['region'])) {
        $payloadregion       = 'Region : ' . $payloadarray['region'];
    }

    if (isset($payloadarray['curriculum'])) {
        $payloadcurriculum   = 'Curriculum : ' . $payloadarray['curriculum'];
    }

    if (isset($payloadarray['client_ip'])) {
        $payloadip           = 'Client ip : ' . $payloadarray['client_ip'];
    }

    if (isset($payloadarray['theme'])) {
        $payloadtheme        = 'Theme : ' . $payloadarray['theme'];
    }

    $siyavulaconfig = get_config('filter_siyavula');

    $function            = '<strong>' . get_string('function_name', 'filter_siyavula') . '</strong> ' . $namefunction;
    $apiroute            = '<strong>' . get_string('api_call', 'filter_siyavula') . '</strong> ' . $apiroute;

    if (empty($response->token)) {
        $message = get_string('message_debug', 'filter_siyavula');
    } else {
        $message = $response->token;
    }

    if (isset($response->errors)) {
        $errors = $response->errors[0]->code . ' - ' . $response->errors[0]->message;
    } else if ($httpcode == 0) {
        $errors = get_string('client_header', 'filter_siyavula');
    }

    if ($id == 'filtersettingsiyavula') {
        if ($siyavulaconfig->debug_enabled == 1 || $CFG->debugdisplay == 1) {
            $printdebuginfo = '<div class="alert alert-danger" role="alert">
                             <span><strong>' . get_string('info_filter', 'filter_siyavula') . '</strong></span>' . $message . ' <br>
                            ' . $function . '<br>
                            ' . $apiroute . '<br>
                              <span><strong>' . get_string('info_payload', 'filter_siyavula') . '</strong></span> <br>
                            ' . $payload . '<br>
                            ' . $payloadname . '<br>
                            ' . $payloadpassword . '<br>
                            ' . $payloadregion . '<br>
                            ' . $payloadcurriculum . '<br>
                            ' . $payloadip . '<br>
                            ' . $payloadtheme . '<br>
                            <span><strong>' . get_string('info_code_response', 'filter_siyavula') . '</strong></span> <br>
                            ' . $httpcode . '<br>
                            <span><strong>' . get_string('info_message_response', 'filter_siyavula') . '</strong></span> <br>
                            ' . $errors . '<br>
                        </div>';

            $printtoken = '<div class="alert alert-danger" role="alert">
                                <span><strong>' . get_string('token', 'filter_siyavula') . '</strong></span>' . $message . ' <br>
                        </div>';

            $printtoken = '<div class="alert alert-danger" role="alert">
                                <span><strong>' . get_string('token', 'filter_siyavula') . '</strong></span>' . $message . ' <br>
                        </div>';

            echo $printdebuginfo;
            echo $printtoken;
        }
    }
}

function validate_params($data) {

    global $CFG, $PAGE, $OUTPUT;

    saved_data($data);

    $clientip = $_SERVER['REMOTE_ADDR'];
    $siyavulaconfig = get_config('filter_siyavula');

    $message = '';
    $success  = '';

    if ($siyavulaconfig->url_base != "https://www.siyavula.com/") {
        $message = '<span>' . get_string('urlbasesuccesserror', 'filter_siyavula') . '</span><br>';
    } else {
        $success  = '<span>' . get_string('urlbasesuccess', 'filter_siyavula') . '</span><br>';
    }

    $gettoken = siyavula_get_user_token($siyavulaconfig, $clientip);

    if ($gettoken == null) {
        $message .= '<span>' . get_string('token_error', 'filter_siyavula') . '</span><br>';
    } else {
        $success  .= '<span>' . get_string('token_generated', 'filter_siyavula') . '</span><br>';
    }

    $externaltoken = siyavula_get_external_user_token($siyavulaconfig, $clientip, $gettoken, $userid = 0);
    if (empty($externaltoken->token)) {
        $message .= '<span>' . get_string('token_externalerror', 'filter_siyavula') . '</span><br>';
    } else {
        $success  .= '<span class="token_success">' . get_string('token_externalgenerated', 'filter_siyavula') . '</span><br>';
    }

    if ($PAGE->pagetype == 'admin-setting-filtersettingsiyavula') {
        if ($message != null) {
            set_config('admin_show_siyavula_notify_error',  true, 'filter_siyavula');
            set_config('admin_show_message_error', $message, 'filter_siyavula');
        } else {
            set_config('admin_show_siyavula_notify_succes', true, 'filter_siyavula');
            set_config('admin_show_message_success', $success, 'filter_siyavula');
        }
    }
}

function saved_data($data) {
    global $PAGE;

    $newdata = (array)$data;
    unset($newdata['section']);
    unset($newdata['action']);
    unset($newdata['sesskey']);
    unset($newdata['return']);

    foreach ($newdata as $name => $value) {
        $name = str_replace('s_filter_siyavula_', '', $name);
        if (is_array($value)) {
            continue;
        }
        set_config($name, $value, 'filter_siyavula');
    }
}

function get_list_users($siyavulaconfig, $token) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $siyavulaconfig->url_base . "api/siyavula/v1/users",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array('JWT: ' . $token),
    ));

    $response = curl_exec($curl);

    $response = json_decode($response);

    // Limit response list users generated with token...
    $limitresult = array_slice((array)$response, 0, 30);

    curl_close($curl);
    return $limitresult;
}

function test_get_external_user_token($siyavulaconfig, $clientip, $token, $email) {
    global $USER, $CFG;

    $curl = curl_init();

    $apiroute = $siyavulaconfig->url_base . "api/siyavula/v1/user/" . $email . '/token';

    curl_setopt_array($curl, array(
        CURLOPT_URL => $siyavulaconfig->url_base . "api/siyavula/v1/user/" . $email . '/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array('JWT: ' . $token),
    ));

    $payload = $token;
    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $response = json_decode($response);

    $name = __FUNCTION__;

    curl_close($curl);

    if (isset($response->errors)) {
        return siyavula_create_user($siyavulaconfig, $token);
    } else {
        return $response;
    }
}

function get_activity_standalone($questionid, $token, $external_token, $baseurl, $randomseed){
    global $USER, $CFG;

    $data = array(
        'template_id' => $questionid,
        'random_seed'  => $randomseed,
    );

    $payload = json_encode($data);

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $baseurl.'api/siyavula/v1/activity/create/standalone',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $payload,
      CURLOPT_HTTPHEADER => array('JWT: ' .$token, 'Authorization: JWT ' .$external_token),
    ));

    $response = curl_exec($curl);
    $response = json_decode($response);

    curl_close($curl);

    return $response;
}

function get_activity_response($token, $usertoken, $baseurl, $activityid, $responseid) {
    global $USER, $CFG;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $baseurl.'api/siyavula/v1/activity/'.$activityid.'/response/'.$responseid,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_HTTPHEADER => array('JWT: ' .$token, 'Authorization: JWT ' .$usertoken),
    ));

    $response = curl_exec($curl);
    $response = json_decode($response);

    curl_close($curl);

    return $response;
}
