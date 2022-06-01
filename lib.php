<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/adminlib.php');
/*
function update_settings_filter_siyavula($a) {

}*/

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

function siyavula_get_external_user_token($siyavulaconfig, $clientip, $token, $userid = 0) {
    global $USER, $CFG;

    $curl = curl_init();

    // Check verify user exitis in siyav
    if ($userid == 0) {
        $email = $USER->email;
    } else {
        $user = core_user::get_user($userid);
        $email = $user->email;
    }

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

    if (($siyavulaconfig->debug_enabled == 1 || $CFG->debugdisplay == 1) && $USER->id != 0) {
        siyavula_debug_message($name, $apiroute, $payload, $response, $httpcode);
    }

    curl_close($curl);

    if (isset($response->errors)) {
        return siyavula_create_user($siyavulaconfig, $token);
    } else {
        return $response;
    }
}

function siyavula_create_user($siyavulaconfig, $token) {

    global $USER, $CFG;

    $data = array(
    'external_user_id' => $USER->email,
    "role" => "Learner",
    "name" => $USER->firstname,
    "surname" => $USER->lastname,
    "password" => "123456",
    "grade" => isset($USER->profile['grade']) ? $USER->profile['Grade'] : 1,
    "country" => $USER->country != '' ? $USER->country : $siyavulaconfig->client_region,
    "curriculum" => isset($USER->profile['curriculum']) ? $USER->profile['Grade'] : $siyavulaconfig->client_curriculum,
    'email' => $USER->email,
    'dialling_code' => '27',
    'telephone' => $USER->phone1
    );

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

    /*error_reporting(E_ALL); // NOT FOR PRODUCTION SERVERS!
    @ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
    $CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
    $CFG->debugdisplay = 1;            // NOT FOR PRODUCTION SERVERS!*/
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
    if ($externaltoken->token == null) {
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


function get_activity_standalone($questionid, $token, $externaltoken, $baseurl, $randomseed) {
    global $USER, $CFG;

    $data = array(
    'template_id' => $questionid,
    'random_seed'  => $randomseed,
    );

    $payload = json_encode($data);

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $baseurl . 'api/siyavula/v1/activity/create/standalone',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => array('JWT: ' . $token, 'Authorization: JWT ' . $externaltoken),
    ));

    $response = curl_exec($curl);
    $response = json_decode($response);

    curl_close($curl);

    return $response;
}

function get_activity_practice($questionid, $token, $externaltoken, $baseurl, $randomseed) {
    global $USER, $CFG;

    $data = array(
    'template_id' => intval($questionid),
    'random_seed'  => $randomseed,
    );

    $payload = json_encode($data);

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $baseurl . 'api/siyavula/v1/activity/create/practice/' . $questionid . '',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => array('JWT: ' . $token, 'Authorization: JWT ' . $externaltoken),
    ));

    $response = curl_exec($curl);
    $response = json_decode($response);

    $questionhtml = $response->response->question_html;
    $newquestionhtml = '';

    $newquestionhtml .= $questionhtml;

    $response->response->question_html = $newquestionhtml;

    curl_close($curl);

    return $response;
}

function get_html_question_standalone($questionapi, $activityid, $responseid) {
    global $CFG, $DB;

    // Enabled mathjax loader
    $siyavulaconfig = get_config('filter_siyavula');
    $torender = '';

    $torender .= '<link rel="stylesheet" href="https://www.siyavula.com/static/themes/emas/siyavula-api/siyavula-api.min.css"/>';
    $torender .= '<link rel="stylesheet" href="' . $CFG->wwwroot . '/filter/siyavula/styles/general.css"/>';

    $torender .= '<main class="sv-region-main emas sv">
                        <div id="monassis" class="monassis monassis--practice monassis--maths monassis--siyavula-api">
                          <div class="question-wrapper">
                            <div class="question-content" data-response="' . $responseid . '" id="' . $activityid . '">
                            ' . $questionapi . '
                            </div>
                          </div>
                        </div>
                    </main>';

    return $torender;
}

function get_html_question_standalone_sequencial($questionapi, $activityid, $responseid) {
    global $CFG, $DB;

    $torender = '';
    // Enabled mathjax loader
    $siyavulaconfig = get_config('filter_siyavula');

    $torender .= '<link rel="stylesheet" href="https://www.siyavula.com/static/themes/emas/siyavula-api/siyavula-api.min.css"/>';
    $torender .= '<link rel="stylesheet" href="' . $CFG->wwwroot . '/filter/siyavula/styles/general.css"/>';

    $torender .= '<main class="sv-region-main emas sv">
                        <div id="monassis" class="monassis monassis--practice monassis--maths monassis--siyavula-api">
                          <div class="question-wrapper">
                            <div class="question-content" data-response="' . $responseid . '" id="' . $activityid . '">
                            ' . $questionapi . '
                            </div>
                          </div>
                        </div>
                    </main>
                    <a href="" id="a_next"><button>Next Question</button></a>
                    <div id="qt"></div>';
    return $torender;
}


function get_html_question_practice($questionapi, $questionchaptertitle, $questionchaptermastery, $questionsectiontitle, $questionmastery) {
    global $CFG, $DB;

    $torenderpr = '';
    // Enabled mathjax loader
    $siyavulaconfig = get_config('filter_siyavula');

    $torenderpr .= '<link rel="stylesheet" href="https://www.siyavula.com/static/themes/emas/siyavula-api/siyavula-api.min.css"/>';
    $torenderpr .= '<link rel="stylesheet" href="' . $CFG->wwwroot . '/filter/siyavula/styles/general.css"/>';

    $torenderpr .= '<main class="sv-region-main emas sv practice-section-question">
                      <div class="item-psq question">
                        <div id="monassis" class="monassis monassis--practice monassis--maths monassis--siyavula-api">
                          <div class="question-wrapper">
                            <div class="question-content">
                            ' . $questionapi . '
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="item-psq">

                        <div class="sv-panel-wrapper sv-panel-wrapper--toc">
                        <div class="sv-panel sv-panel--dashboard sv-panel--toc sv-panel--toc-modern no-secondary-section">
                          <div class="sv-panel__header">
                            <div class="sv-panel__title">
                              Busy practising
                            </div>
                          </div>
                          <div class="sv-panel__section sv-panel__section--primary" id="mini-dashboard-toc-primary">
                            <div class="sv-panel__section-header">
                              <div class="sv-panel__section-title">This exercise is from:</div>
                            </div>
                            <div class="sv-panel__section-body">
                              <div class="sv-toc sv-toc--dashboard-mastery-primary">
                                <ul class="sv-toc__chapters">
                                  <li class="sv-toc__chapter">
                                    <div class="sv-toc__chapter-header">
                                      <div class="sv-toc__chapter-title"><span id="chapter-mastery-title">' . $questionchaptertitle . '</span></div>
                                      <div class="sv-toc__chapter-mastery">
                                        <div class="sv-toc__section-mastery">
                                          <progress class="progress" id="chapter-mastery" value="' . round($questionchaptermastery) . '" max="100" data-text="' . round($questionchaptermastery) . '%"></progress>
                                        </div>
                                      </div>
                                    </div>
                                    <div class="sv-toc__chapter-body">
                                      <ul class="sv-toc__sections">
                                          <li class="sv-toc__section ">
                                            <div class="sv-toc__section-header">
                                              <div class="sv-toc__section-title">
                                                <span id="section-mastery-title">' . $questionsectiontitle . '</span>
                                              </div>
                                              <div class="sv-toc__section-mastery">
                                                <progress class="progress" id="section-mastery" value="' . round($questionmastery) . '" max="100" data-text="' . round($questionmastery) . '%"></progress><br>
                                              </div>
                                            </div>
                                          </li>
                                      </ul>
                                    </div>
                                  </li>
                                </ul>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </main>';

    return $torenderpr;
}

// Html render practice session
function retry_question_html_practice($activityid, $responseid, $token, $externaltoken, $baseurl) {
    global $USER, $CFG;

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $baseurl . 'api/siyavula/v1/activity/' . $activityid . '/response/' . $responseid . '/retry',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_HTTPHEADER => array('JWT: ' . $token, 'Authorization: JWT ' . $externaltoken),
    ));

    $response = curl_exec($curl);
    $response = json_decode($response);

    $questionhtml = $response->response->question_html;
    $newquestionhtml = '';

    $newquestionhtml .= $questionhtml;

    $response->response->question_html = $newquestionhtml;

    curl_close($curl);

    return $response;
}
