<?php
require_once ($CFG->dirroot . '/filter/siyavula/lib.php');

class filter_siyavula extends moodle_text_filter
{

    public function check_if_next($all_ids, $siyavula_activity_id)
    {
        //Verify if next id exist
        $flag = false;
        $next_id = false;
        foreach ($all_ids as $id)
        {
            if ($flag == true)
            {
                $next_id = $id;
                break;
            }
            if ($id == $siyavula_activity_id)
            {
                $flag = true;
            }
        }
        return $next_id;
    }

    public function filter($text, array $options = array())
    {
        global $OUTPUT, $USER, $PAGE, $CFG, $DB;
        
        //Verify if user not authenticated
        $user_auth = false;
        if (isguestuser() || $USER == NULL)
        {
            $user_auth = true;
            header('Location: ' . $CFG->wwwroot . '/login/index.php');
            exit();
        }
        
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $siyavula_config = get_config('filter_siyavula');

        $token = siyavula_get_user_token($siyavula_config, $client_ip);

        $user_token = siyavula_get_external_user_token($siyavula_config, $client_ip, $token);
        
        //[[sy-2632]] or [[sy-2632,sy-4429]] - Standalone questions
        $findsy = 'sy-';
        $possy = strpos($text, $findsy);

        //[[syp-204]] - Practice questions 
        $findpr = 'syp-';
        $pospr = strpos($text, $findpr);

        if ($possy != false)
        {
            $syquestion = true;
        }
        else if ($pospr != false)
        {
            $qtpractice = true; 
        }
        
        //Get type siyavulaqt
        $compare_scale_clause = $DB->sql_compare_text('questiontext')  . ' = ' . $DB->sql_compare_text(':type');
        $typename = $DB->get_record_sql("select * from {question} where $compare_scale_clause",array('type' => $text));

        //Get user inside in quiz attempt
        $url = $_SERVER["REQUEST_URI"];
        $findme  = '/mod/quiz/attempt.php';
        $pos = strpos($url, $findme);
        
        //Question type standalone
        if (!$user_auth && $syquestion)
        {
            $newtext = strip_tags($text);
            $global_ids = [];
        
            $re = '/\[{2}[sy\-\d{1,},?]*\]{2}/m';
            preg_match_all($re, $newtext, $matches);
            //Verify format only sy-
            if(empty($matches[0])){
                $textclear = str_replace(['sy-'], '', $newtext);
                $ids = explode(',', $textclear);
                $global_ids = array_merge($global_ids, $ids);
            }else{
                foreach ($matches[0] as $match){
                    $ids = str_replace(['[[', ']]', 'sy-'], '', $match);
                    $ids = explode(',', $ids);
                    $global_ids = array_merge($global_ids, $ids);
                } 
            }

            $all_ids = $global_ids;
    
            // Check query
            $sid = optional_param('templateId', false, PARAM_RAW);
            $param_all_id = optional_param('all_ids', false, PARAM_RAW);
            $sectionId = optional_param('sectionid', false, PARAM_RAW);
            $currenturl = $PAGE->URL;
            if ($param_all_id)
            {
                $all_ids = explode('|', $param_all_id);
                $first_id = $all_ids[0];

                $show_id = optional_param('show_id', $first_id, PARAM_INT); // The actual show template id is optional, if not get, put the first id found i paral all_ids
                $siyavula_activity_id = $show_id;
                $next_id = $this->check_if_next($all_ids, $siyavula_activity_id);

                $templatecontext[] = ['template_id' => $siyavula_activity_id, 'user_token' => $user_token->token, 'token' => $token, 'baseUrl' => $siyavula_config->url_base, 'randomSeed' => 3527, 'all_ids' => implode('|', $all_ids) , 'current_url' => $currenturl, 'next' => $next_id, ];
                echo '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/MathJax.js?config=TeX-MML-AM_HTMLorMML-full"></script>';
                $result = $OUTPUT->render_from_template('filter_siyavula/standalone', ["renderall" => $templatecontext]);
            }
            else
            {
                foreach ($global_ids as $gid)
                {
                    $insert_point = strpos($gid, '</siyavula-q>');

                    $siyavula_activity_id = $gid;
                    $next_id = $this->check_if_next($global_ids, $siyavula_activity_id);
                    $templatecontext[] = ['template_id' => $siyavula_activity_id, 'user_token' => $user_token->token, 'token' => $token, 'baseUrl' => $siyavula_config->url_base, 'randomSeed' => 3527, 'all_ids' => implode('|', $global_ids) , 'current_url' => $currenturl, 'next' => $next_id, ];

                    echo '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/MathJax.js?config=TeX-MML-AM_HTMLorMML-full"></script>';

                    $result = $OUTPUT->render_from_template('filter_siyavula/standalone', ["renderall" => $templatecontext]);
                    break;
                }
            }
        }else if($pos === 0 && $typename->qtype === "siyavulaqt"){
            $newtext = strip_tags($text);
            
            $global_ids = [];
        
            $ids = explode(',', $newtext);
            $global_ids = array_merge($global_ids, $ids);
    
            $all_ids = $global_ids;
    
            // Check query
            $sid = optional_param('templateId', false, PARAM_RAW);
            $param_all_id = optional_param('all_ids', false, PARAM_RAW);
            $sectionId = optional_param('sectionid', false, PARAM_RAW);
            $currenturl = $PAGE->URL;
            if ($param_all_id)
            {
                $all_ids = explode('|', $param_all_id);
                $first_id = $all_ids[0];

                $show_id = optional_param('show_id', $first_id, PARAM_INT); // The actual show template id is optional, if not get, put the first id found i paral all_ids
                $siyavula_activity_id = $show_id;
                $next_id = $this->check_if_next($all_ids, $siyavula_activity_id);

                $templatecontext[] = ['template_id' => $siyavula_activity_id, 'user_token' => $user_token->token, 'token' => $token, 'baseUrl' => $siyavula_config->url_base, 'randomSeed' => 3527, 'all_ids' => implode('|', $all_ids) , 'current_url' => $currenturl, 'next' => $next_id, ];
                echo '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/MathJax.js?config=TeX-MML-AM_HTMLorMML-full"></script>';
                $result = $OUTPUT->render_from_template('filter_siyavula/standalone', ["renderall" => $templatecontext]);
            }
            else
            {
                foreach ($global_ids as $gid)
                {
                    $insert_point = strpos($gid, '</siyavula-q>');

                    $siyavula_activity_id = $gid;
                    $next_id = $this->check_if_next($global_ids, $siyavula_activity_id);
                    $templatecontext[] = ['template_id' => $siyavula_activity_id, 'user_token' => $user_token->token, 'token' => $token, 'baseUrl' => $siyavula_config->url_base, 'randomSeed' => 3527, 'all_ids' => implode('|', $global_ids) , 'current_url' => $currenturl, 'next' => $next_id, ];

                    echo '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/MathJax.js?config=TeX-MML-AM_HTMLorMML-full"></script>';

                    $result = $OUTPUT->render_from_template('filter_siyavula/standalone', ["renderall" => $templatecontext]);
                    break;
                }
            }
            
        }
        //Question type practice
        if (!$user_auth && $qtpractice)
        {
            $newtext = strip_tags($text);
            $re = '/\[{2}[syp\-\d{1,},?]*\]{2}/m';

            preg_match_all($re, $newtext, $matches);
            $global_ids = [];
            foreach ($matches[0] as $match)
            {
                $ids = str_replace(['[[', ']]', 'syp-'], '', $match);
                $ids = explode(',', $ids);
                $global_ids = array_merge($global_ids, $ids);
            }
            
            foreach ($global_ids as $gid)
            {

                $insert_point = strpos($gid, '</siyavula-q>');

                $siyavula_activity_id = $gid;

                $templatepractice[] = [
                    'sectionId' => $siyavula_activity_id, 
                    'user_token' => $user_token->token, 
                    'token' => $token, 
                    'baseUrl' => $siyavula_config->url_base, ];
                    
                 echo '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/MathJax.js?config=TeX-MML-AM_HTMLorMML-full"></script>';

                $result = $OUTPUT->render_from_template('filter_siyavula/practice_responsive', ["renderpractice" => $templatepractice]);
                break;
            }
        }

        //Render questions not apply format siyavula
        if(!empty($result)){
            return $result;
        }else{
            return $text;
        }
    }
}