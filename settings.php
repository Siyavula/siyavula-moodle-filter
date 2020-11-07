<?php

$settings->add(new admin_setting_configtext('filter_siyavula/url_base',
        get_string('siyavula_url_base', 'filter_siyavula'),
        get_string('siyavula_url_base_desc', 'filter_siyavula'), '', PARAM_NOTAGS));

$settings->add(new admin_setting_configtext('filter_siyavula/client_name',
        get_string('siyavula_client_name', 'filter_siyavula'),
        get_string('siyavula_client_name_desc', 'filter_siyavula'), '', PARAM_NOTAGS));

$settings->add(new admin_setting_configpasswordunmask('filter_siyavula/client_password',
        get_string('siyavula_client_password', 'filter_siyavula'),
        get_string('siyavula_client_password_desc', 'filter_siyavula'), '', PARAM_NOTAGS));
        
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