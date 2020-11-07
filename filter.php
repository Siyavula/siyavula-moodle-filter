<?php

require_once($CFG->dirroot.'/filter/siyavula/lib.php');

class filter_siyavula extends moodle_text_filter {
    
    public function filter($text, array $options = array()) {
        
        if(!is_string($text) or empty($text)){
            return $text;
        }
        
        //[[sy-2632]]
        if(stripos($text, '[[sy-') === false){
            return $text;
        }
        
        $start_pos = stripos($text, '[[sy-') + 5;
        $end_pos = stripos($text, ']]');
        
        if($end_pos <= $start_pos){
            return $text;
        }
        
        $count = $end_pos - $start_pos;
        
        $siyavula_activity_id = substr($text, $start_pos, $count);
        
        global $OUTPUT, $USER, $PAGE, $CFG;
        
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $siyavula_config = get_config('filter_siyavula');
        
        $token = siyavula_get_user_token($siyavula_config, $client_ip);
        
        $user_token = siyavula_get_external_user_token($siyavula_config, $client_ip, $token);
        
        $insert_point = strpos($text, '</siyavula-q>');
        
        $templatecontext = array(
                'template_id' => $siyavula_activity_id,
                'user_token' => $user_token->token,
                'token' => $token,
                'baseUrl' => $siyavula_config->url_base,
                'randomSeed' => 3527,
            );
        echo '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/MathJax.js?config=TeX-MML-AM_HTMLorMML-full"></script>
                <script src="https://www.siyavula.com/static/themes/emas/siyavula-api/siyavula-api.js"></script>';
        $result = $OUTPUT->render_from_template('filter_siyavula/standalone', $templatecontext);
        
        $result = str_replace('[[sy-'.$siyavula_activity_id.']]', $result, $text);
        
        return $result;
    }
}