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
        
        /**********************/
        // I want to validate if the current paragraph contains the
        // pattern that indicates that the question should be rendered
        
        $qtpractice = false; 
        $syquestion = false;
        
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
        }else{
            return $text; // we'll return the text with no changes
        }
        
        /**********************/
        
        //Verify if user not authenticated
        $user_auth = false;
        if (isguestuser() || $USER == NULL)
        {
            $user_auth = true;
            header('Location: ' . $CFG->wwwroot . '/login/index.php');
            exit();
        }
        
        $client_ip       = $_SERVER['REMOTE_ADDR'];
        $siyavula_config = get_config('filter_siyavula');

        $token = siyavula_get_user_token($siyavula_config, $client_ip);
       
        $user_token = siyavula_get_external_user_token($siyavula_config, $client_ip, $token);
        
        //Get user inside in quiz attempt
        $url = $_SERVER["REQUEST_URI"];
        $findme  = '/mod/quiz/attempt.php';
        $pos = strpos($url, $findme);
        
        //Get type siyavulaqt
        $compare_scale_clause = $DB->sql_compare_text('questiontext')  . ' = ' . $DB->sql_compare_text(':type');
        $typename = $DB->get_record_sql("select * from {question} where $compare_scale_clause",array('type' => $text));

        //Question type standalone
        if ($syquestion && $pos === false)
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
                if(count($matches[0]) == 1) { // Only found one
                  $is_secuencial = true;
                  // Is secuencial
                  $ids = str_replace(['[[', ']]', 'sy-'], '', $matches[0][0]);
                  $ids = explode(',', $ids);
                  if(count($ids) > 1) { // If explode give more than one
                    $global_ids = array_merge($global_ids, $ids); // Then is secuencial, show Next button
                    $with_text = false; // Is secuencial with not text
                  }
                  else {
                    //Only one question, no more
                    $text_to_replace_render = $matches[0][0];
                    $with_text = true; // If only one, maybe have text too
                  }
                }
                else { // Multiple Questions with texts
                  $is_secuencial = false;
                  $with_text = true; // Maybe not have text, but maybe yes
                  foreach ($matches[0] as $match){
                    $text_to_replace_render[] = $match;
                  } 
                }
            }

            $all_ids = $global_ids;
            
            if($is_secuencial == true && $with_text == true)  // At least, one question with text too
            {
              $next_id = false; // No put the Next button
              $siyavula_activity_id = str_replace(['[[', ']]', 'sy-'], '', $text_to_replace_render); // Only the number
              
              $external_token = $user_token->token;
              $activityType = 'standalone';
              $template_id  = $siyavula_activity_id;
              $randomseed = $siyavula_config->randomseed;
              $baseurl = $siyavula_config->url_base;
              $currenturl = $PAGE->URL;
              
              $questionapi = get_activity_standalone($siyavula_activity_id,$token, $user_token->token,$siyavula_config->url_base,3527);

              $activityid  = $questionapi->activity->id;
              $responseid  = $questionapi->response->id;
    
              $htmlquestion = get_html_question_standalone($questionapi->response->question_html,$activityid,$responseid);
             
              echo str_replace($text_to_replace_render, $htmlquestion, $text);
              $result = $PAGE->requires->js_call_amd('filter_siyavula/external', 'init', [$baseurl,$token,$external_token,$activityid,$responseid,$idsq,$currenturl->__toString(),$next_id,$siyavula_activity_id]);
              
            }
            else if($is_secuencial == true && $with_text == false) { // Is secuencial, show the "Next" Button
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
  
                  $external_token = $user_token->token;
                  $activityType = 'standalone';
                  $template_id  = $siyavula_activity_id;
                  $randomseed = '3527';
                  $baseurl = $siyavula_config->url_base;
                  $idsq = implode('|', $all_ids);
              
                  $questionapi = get_activity_standalone($siyavula_activity_id,$token, $user_token->token,$siyavula_config->url_base,3527);
                  $activityid  = $questionapi->activity->id;
                  $responseid  = $questionapi->response->id;
                  
                  echo '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/MathJax.js?config=TeX-MML-AM_HTMLorMML-full"></script>';
                  echo '<link rel="stylesheet" href="https://www.siyavula.com/static/themes/emas/siyavula-api/siyavula-api.min.css"/>';
                  echo '<link rel="stylesheet" href="https://www.siyavula.com/static/themes/emas/question-api/question-api.min.css"/>';
                  echo '<link rel="stylesheet" href="'.$CFG->wwwroot.'/filter/siyavula/styles/general.css"/>';
                  
                  echo '<main class="sv-region-main emas sv">
                            <div id="monassis" class="monassis monassis--practice monassis--maths monassis--siyavula-api">
                              <div class="question-wrapper">
                                <div class="question-content">
                                '.$questionapi->response->question_html.'
                                </div>
                              </div>
                            </div>
                        </main>
                        <a href="" id="a_next"><button>Next Question</button></a>';
                  
                  $result = $PAGE->requires->js_call_amd('filter_siyavula/external', 'init', [$baseurl,$token,$external_token,$activityid,$responseid,$idsq,$currenturl->__toString(),$next_id,$siyavula_activity_id]);
           
              }else{
                
                  foreach ($global_ids as $gid)
                  {
                      $siyavula_activity_id = $gid;
                      $next_id = $this->check_if_next($global_ids, $siyavula_activity_id);
                      $templatecontext[] = ['template_id' => $siyavula_activity_id, 'user_token' => $user_token->token, 'token' => $token, 'baseUrl' => $siyavula_config->url_base, 'randomSeed' => 3527, 'all_ids' => implode('|', $global_ids) , 'current_url' => $currenturl, 'next' => $next_id, ];
                      
                      $idsq = implode('|', $all_ids);
                      $external_token = $user_token->token;
                      $activityType = 'standalone';
                      $template_id  = $siyavula_activity_id;
                      $randomseed = '3527';
                      $baseurl = $siyavula_config->url_base;
                      
                      $questionapi = get_activity_standalone($siyavula_activity_id,$token, $user_token->token,$siyavula_config->url_base,3527);
                      $activityid  = $questionapi->activity->id;
                      $responseid  = $questionapi->response->id;
            
                      echo '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/MathJax.js?config=TeX-MML-AM_HTMLorMML-full"></script>';
                      echo '<link rel="stylesheet" href="https://www.siyavula.com/static/themes/emas/siyavula-api/siyavula-api.min.css"/>';
                      echo '<link rel="stylesheet" href="https://www.siyavula.com/static/themes/emas/question-api/question-api.min.css"/>';
                      echo '<link rel="stylesheet" href="'.$CFG->wwwroot.'/filter/siyavula/styles/general.css"/>';
                    
                      echo '<main class="sv-region-main emas sv">
                                <div id="monassis" class="monassis monassis--practice monassis--maths monassis--siyavula-api">
                                  <div class="question-wrapper">
                                    <div class="question-content">
                                    '.$questionapi->response->question_html.'
                                    </div>
                                  </div>
                                </div>
                            </main>
                            <a href="" id="a_next"><button>Next Question</button></a>';
                     
                      $result = $PAGE->requires->js_call_amd('filter_siyavula/external', 'init', [$baseurl,$token,$external_token,$activityid,$responseid,$idsq,$currenturl->__toString(),$next_id,$siyavula_activity_id]);
                      //$result = $OUTPUT->render_from_template('filter_siyavula/standalone', ["renderall" => $templatecontext]);*/
                      
                      break;
                  }
              }              
            }
            else if($is_secuencial == false && $with_text == true) // Is not secuencial, multiple questions and maybe have text
            {
              $to_echo = $text;
              foreach($text_to_replace_render as $ttrr)
              {
                $next_id = false; // No put the Next button
                $siyavula_activity_id = str_replace(['[[', ']]', 'sy-'], '', $ttrr); // Only the number
                
                $external_token = $user_token->token;
                $activityType = 'standalone';
                $template_id  = $siyavula_activity_id;
                $randomseed = '3527';
                $baseurl = $siyavula_config->url_base;
                $currenturl = $PAGE->URL;
                
                $questionapi = get_activity_standalone($siyavula_activity_id,$token, $user_token->token,$siyavula_config->url_base,3527);
                $activityid  = $questionapi->activity->id;
                $responseid  = $questionapi->response->id;
      
                $htmlquestion = get_html_question_standalone($questionapi->response->question_html,$activityid,$responseid);

                $to_echo = str_replace($ttrr, $htmlquestion, $to_echo);
              }
              echo $to_echo;
              $result = $PAGE->requires->js_call_amd('filter_siyavula/external', 'init', [$baseurl,$token,$external_token,$activityid,$responseid,$idsq,$currenturl->__toString(),$next_id,$siyavula_activity_id]);
              
            }
  
        }
        else if($pos == 0 && $typename->qtype == "siyavulaqt") // This is quiz
        {
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
  
                    $siyavula_activity_id = $gid;
                    $replacetag = str_replace("sy-",' ',$siyavula_activity_id);
                    $next_id = $this->check_if_next($global_ids, $replacetag);
                    $templatecontext[] = ['template_id' => $replacetag, 'user_token' => $user_token->token, 'token' => $token, 'baseUrl' => $siyavula_config->url_base, 'randomSeed' => 3527, 'all_ids' => implode('|', $global_ids) , 'current_url' => $currenturl, 'next' => $next_id, ];

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
                $siyavula_activity_id = $gid;

                $templatepractice[] = [
                    'sectionId' => $siyavula_activity_id, 
                    'user_token' => $user_token->token, 
                    'token' => $token, 
                    'baseUrl' => $siyavula_config->url_base, ];
                    
                $external_token = $user_token->token;
                $activityType = 'practice';
                $template_id  = $siyavula_activity_id;
                $baseurl = $siyavula_config->url_base;
                
                $questionapi = get_activity_practice($siyavula_activity_id,$token, $user_token->token,$siyavula_config->url_base);
                $activityid  = $questionapi->activity->id;
                $responseid  = $questionapi->response->id;
                
                $htmlpractice = get_html_question_practice($questionapi->response->question_html,$questionapi->practice->chapter->title,$questionapi->practice->chapter->mastery,$questionapi->practice->section->title,$questionapi->practice->section->mastery);
                echo $htmlpractice;
                $result = $PAGE->requires->js_call_amd('filter_siyavula/externalpractice', 'init', [$baseurl,$token,$external_token,$activityid,$responseid]);

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