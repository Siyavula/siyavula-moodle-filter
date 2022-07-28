<?php

 $functions = array(
    'filter_siyavula_save_activity_data' => array(
        'classname'   => 'filter_siyavula_external',
        'methodname'  => 'save_activity_data',
        'description' => 'Save the actitivity and response id to be used in question feedback.',
        'type'        => 'write',
        'ajax'          => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'loginrequired' => false,
    ),
 );
