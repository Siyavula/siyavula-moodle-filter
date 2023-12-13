<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/filter/siyavula/lib.php');
global $PAGE, $OUTPUT;

// Course_module ID.
$id = optional_param('section', '', PARAM_TEXT);

// Settings page.
$settings = new admin_settingpage(
    'filtersettingsiyavula',
    new lang_string('filtername', 'filter_siyavula'),
    'moodle/site:config',
    false
);

$urlbase = new admin_setting_configtext(
    'filter_siyavula/url_base',
    get_string('siyavula_url_base', 'filter_siyavula'),
    get_string('siyavula_url_base_desc', 'filter_siyavula'),
    'https://www.siyavula.com/',
    PARAM_NOTAGS
);
$tokenget = '';
$listusers = '';

$clientname = new admin_setting_configtext(
    'filter_siyavula/client_name',
    get_string('siyavula_client_name', 'filter_siyavula'),
    get_string('siyavula_client_name_desc', 'filter_siyavula'),
    '',
    PARAM_NOTAGS
);

$clientpassword = new admin_setting_configpasswordunmask(
    'filter_siyavula/client_password',
    get_string('siyavula_client_password', 'filter_siyavula'),
    get_string('siyavula_client_password_desc', 'filter_siyavula'),
    '',
    PARAM_NOTAGS
);

$options = array(
    'ZA' => 'ZA - South Africa',
    'NG' => 'NG - Nigeria',
    'RW' => 'RW - Rwanda',
    'INTL' => 'INTL - International'
);

$clientregion = new admin_setting_configselect(
    'filter_siyavula/client_region',
    get_string('siyavula_region', 'filter_siyavula'),
    get_string('siyavula_region_desc', 'filter_siyavula'),
    'INTL',
    $options
);

$options = array(
    'CAPS' => 'CAPS - South Africa (CAPS & IEB)',
    'NG' => 'NG - Nigeria (NERDC)',
    'CBC' => 'CBC - Rwanda',
    'INTL' => 'INTL - International (topic-based)'
);

$clientcurriculum = new admin_setting_configselect(
    'filter_siyavula/client_curriculum',
    get_string('siyavula_curriculum', 'filter_siyavula'),
    get_string('siyavula_curriculum_desc', 'filter_siyavula'),
    'INTL',
    $options
);

$showretry = new admin_setting_configcheckbox(
    'filter_siyavula/showretry',
    get_string('siyavula_showretry', 'filter_siyavula'),
    get_string('siyavula_showretry', 'filter_siyavula'),
    0
);

$showlivepreview = new admin_setting_configcheckbox(
    'filter_siyavula/showlivepreview',
    get_string('siyavula_showlivepreview', 'filter_siyavula'),
    get_string('siyavula_showlivepreview', 'filter_siyavula'),
    0
);


$options = array(
    '0' => get_string('disabled_debugging', 'filter_siyavula'),
    '1' => get_string('enabled_debugging', 'filter_siyavula'),
);

$debugenabled = new admin_setting_configselect(
    'filter_siyavula/debug_enabled',
    get_string('siyavula_debuginfo', 'filter_siyavula'),
    get_string('siyavula_debuginfo_desc', 'filter_siyavula'),
    '0',
    $options
);

// Only if the current page is the filter siyavula settings.
if ($id == 'filtersettingsiyavula') {
    $clientip = $_SERVER['REMOTE_ADDR'];
    $siyavulaconfig = get_config('filter_siyavula');
    $gettoken = siyavula_get_user_token($siyavulaconfig, $clientip);
    $tokenresponse = $gettoken;
    $getusers = get_list_users($siyavulaconfig, $gettoken);

    if (!empty($tokenresponse)) {
        $tokenget = new admin_setting_description(
            'filter_siyavula/token_get',
            get_string('siyavula_tokenget', 'filter_siyavula'),
            $tokenresponse
        );
        $settings->add($tokenget);
    }

    if (!empty($getusers) && !empty($tokenresponse)) {
        foreach ($getusers as $user) {
            $data[] = $user->email;
            $testtoken = '<a target="_blank" href="' . $CFG->wwwroot .
                '/filter/siyavula/test_external_usertoken.php?token=' . $tokenresponse .
                '">' . get_string('test_external_usertoken', 'filter_siyavula') . '</a>';
        }
        $listusers = new admin_setting_configselect(
            'users_filter_siyavula/list_users',
            get_string('siyavula_list_users', 'filter_siyavula'),
            $testtoken,
            '',
            $data
        );

        $settings->add($listusers);
    }

    // Show messages if filter response correct create Token and external toke....
    if (get_config('filter_siyavula', 'admin_show_siyavula_notify_error') == true) {
            $siyavulaconfigmessages = get_config('filter_siyavula');

        if (isset($siyavulaconfigmessages->admin_show_message_error)) {
            $messages = $siyavulaconfigmessages->admin_show_message_error;
        }

        \core\notification::error($messages);
        set_config('admin_show_siyavula_notify_error', false, 'filter_siyavula');
        set_config('admin_show_message_error', false, 'filter_siyavula');
    } else if (get_config('filter_siyavula', 'admin_show_siyavula_notify_succes') == true) {
        $siyavulaconfigmessages = get_config('filter_siyavula');

        if (isset($siyavulaconfigmessages->admin_show_message_success)) {
            $messages = $siyavulaconfigmessages->admin_show_message_success;
        }

        \core\notification::info($messages);
        set_config('admin_show_siyavula_notify_succes', false, 'filter_siyavula');
        set_config('admin_show_message_success', false, 'filter_siyavula');
    }
}

if ($PAGE->bodyid == 'page-admin-setting-filtersettingsiyavula' and
    $data = data_submitted() and confirm_sesskey() and isset($data->action) and
    $data->action == 'save-settings') {
    validate_params($data);
}

$settings->add($urlbase);
$settings->add($clientname);
$settings->add($clientpassword);
$settings->add($clientregion);
$settings->add($clientcurriculum);
$settings->add($showretry);
$settings->add($showlivepreview);
$settings->add($debugenabled);
