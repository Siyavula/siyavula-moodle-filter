<?php
defined('MOODLE_INTERNAL') || die();
require_once ($CFG->dirroot . '/filter/siyavula/lib.php');
global $PAGE, $OUTPUT;

$settings->add(new admin_setting_configtext('filter_siyavula/url_base',
        get_string('siyavula_url_base', 'filter_siyavula'),
        get_string('siyavula_url_base_desc', 'filter_siyavula'), 'https://www.siyavula.com/', PARAM_NOTAGS));

$settings->add(new admin_setting_configtext('filter_siyavula/client_name',
        get_string('siyavula_client_name', 'filter_siyavula'),
        get_string('siyavula_client_name_desc', 'filter_siyavula'), ' ', PARAM_NOTAGS));

$settings->add(new admin_setting_configpasswordunmask('filter_siyavula/client_password',
        get_string('siyavula_client_password', 'filter_siyavula'),
        get_string('siyavula_client_password_desc', 'filter_siyavula'), ' ', PARAM_NOTAGS));
        
$options = array(
                'ZA' => 'ZA - South Africa',
                'NG' => 'NG - Nigeria',
                'RW' => 'RW - Rwanda',
                'INTL' => 'INTL - International'
        );

$settings->add(new admin_setting_configselect('filter_siyavula/client_region',
        get_string('siyavula_region', 'filter_siyavula'),
        get_string('siyavula_region_desc', 'filter_siyavula'), 'INTL', $options));

$options = array(
                'CAPS' => 'CAPS - South Africa (CAPS & IEB)',
                'NG' => 'NG - Nigeria (NERDC)',
                'CBC' => 'CBC - Rwanda',
                'INTL' => 'INTL - International (topic-based)'
        );

$settings->add(new admin_setting_configselect('filter_siyavula/client_curriculum',
        get_string('siyavula_curriculum', 'filter_siyavula'),
        get_string('siyavula_curriculum_desc', 'filter_siyavula'), 'INTL', $options));
        

$settings->add(new admin_setting_configcheckbox('filter_siyavula/mathjax', get_string('siyavula_mathjax', 'filter_siyavula'),
           get_string('siyavula_mathjax', 'filter_siyavula'), 1));
        
        
$options = array(
                '0' => get_string('disabled_debugging','filter_siyavula'),
                '1' => get_string('enabled_debugging','filter_siyavula'),
        );
        
$settings->add(new admin_setting_configselect('filter_siyavula/debug_enabled',
        get_string('siyavula_debuginfo', 'filter_siyavula'),
        get_string('siyavula_debuginfo_desc', 'filter_siyavula'), '0', $options));
        

$client_ip = $_SERVER['REMOTE_ADDR'];
$siyavula_config = get_config('filter_siyavula');
$get_token = siyavula_get_user_token($siyavula_config,$client_ip);
$tokenresponse = $get_token;

$get_users = get_list_users($siyavula_config,$get_token);


if(!empty($tokenresponse)){
   $settings->add(new admin_setting_description('filter_siyavula/token_get',
        get_string('siyavula_tokenget', 'filter_siyavula'),
        $tokenresponse));
} 

if(!empty($get_users) && !empty($tokenresponse)){
        foreach($get_users as $user){
                $data[] = $user->email;
    
        $test_token = '<a target="_blank" href="'.$CFG->wwwroot.'/filter/siyavula/test_external_usertoken.php?token='.$tokenresponse.'">'.get_string('test_external_usertoken', 'filter_siyavula').'</a>';
        $settings->add(new admin_setting_configselect('users_filter_siyavula/list_users',
                get_string('siyavula_list_users', 'filter_siyavula'),
                $test_token,'' ,$data)); 
                
        }
}

if ($data = data_submitted() and confirm_sesskey() and isset($data->action) and $data->action == 'save-settings') {
        validate_params($data);
} 

//Show messages if filter response correct create Token and external toke....
if(get_config('filter_siyavula', 'admin_show_siyavula_notify_error') == true){
        $siyavula_config_messages = get_config('filter_siyavula');

        if(isset($siyavula_config_messages->admin_show_message_error)){
           $messages = $siyavula_config_messages->admin_show_message_error;
        }
        
        \core\notification::error($messages);
        set_config('admin_show_siyavula_notify_error', false, 'filter_siyavula');
        set_config('admin_show_message_error', false, 'filter_siyavula');
        
        
}else if(get_config('filter_siyavula', 'admin_show_siyavula_notify_succes') == true){
        $siyavula_config_messages = get_config('filter_siyavula');

        if(isset($siyavula_config_messages->admin_show_message_success)){
           $messages = $siyavula_config_messages->admin_show_message_success;
        }
        
        \core\notification::info($messages);
        set_config('admin_show_siyavula_notify_succes', false, 'filter_siyavula');
        set_config('admin_show_message_success', false, 'filter_siyavula');
}