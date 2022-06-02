<?php

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/filter/siyavula/lib.php');

$token = required_param('token', PARAM_RAW);

$title = get_string('test_external_usertoken', 'filter_siyavula');
$PAGE->set_url('/filter/siyavula/test_external_usertoken.php');
$PAGE->set_title(format_string($title));
$PAGE->set_heading(format_string($title));
$PAGE->set_context(context_system::instance());

echo $OUTPUT->header();
$html = '';
$html .= '<form action="' . $CFG->wwwroot . '/filter/siyavula/test_external_usertoken.php?token=' .
            $token . '" method="post" id="form_test_token">
            <label for="email">' . get_string('email_token_external', 'filter_siyavula') . '</label>
            <input type="text" id="email" name=email><br><br>
            <button type="submit" name="testToken" class="btn btn-primary">' .
            get_string('btnsendtoken', 'filter_siyavula') . '</button>
        </form>';

if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['testToken'])) {
    $email = ($_POST['email']);

    $clientip       = $_SERVER['REMOTE_ADDR'];
    $siyavulaconfig = get_config('filter_siyavula');

    $externaltoken = test_get_external_user_token($siyavulaconfig, $clientip, $token, $email);

    if (isset($externaltoken->token)) {
        $html .= '<div class="alert alert-success" role="alert">
                      ' . get_string('token_externalgenerated', 'filter_siyavula') . ' ' . $externaltoken->token . '
                   </div>';
    } else {
        $html .= '<div class="alert alert-danger" role="alert">
                      ' . get_string('error', 'filter_siyavula') . ' ' .
                      $externaltoken->errors[0]->code . ' ' . $externaltoken->errors[0]->message . '
                   </div>';
    }
}

echo $html;

echo $OUTPUT->footer();
