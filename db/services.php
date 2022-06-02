<?php

 $functions = array(
    'filter_siyavula_submit_answers_siyavula' => array(
        'classname'   => 'filter_siyavula_external',
        'methodname'  => 'submit_answer',
        'description' => 'Send Via API response answers.',
        'type'        => 'read',
        'ajax'          => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'loginrequired' => false,
    ),
 );
