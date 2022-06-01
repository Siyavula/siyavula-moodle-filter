<?php
require_once($CFG->dirroot . '/filter/siyavula/lib.php');

class filter_siyavula extends moodle_text_filter {

    public function check_if_next($allids, $siyavulaactivityid) {
        // Verify if next id exist
        $flag = false;
        $nextid = false;
        foreach ($allids as $id) {
            if ($flag == true) {
                $nextid = $id;
                break;
            }
            if ($id == $siyavulaactivityid) {
                $flag = true;
            }
        }
        return $nextid;
    }

    public function filter($text, array $options = array()) {

        global $OUTPUT, $USER, $PAGE, $CFG, $DB;

        /**********************/
        // I want to validate if the current paragraph contains the
        // pattern that indicates that the question should be rendered

        $qtpractice = false;
        $syquestion = false;

        // [[sy-2632]] or [[sy-2632,sy-4429]] - Standalone questions
        $findsy = 'sy-';
        $possy = strpos($text, $findsy);

        // [[syp-204]] - Practice questions
        $findpr = 'syp-';
        $pospr = strpos($text, $findpr);

        if ($possy != false) {
            $syquestion = true;
        } else if ($pospr != false) {
            $qtpractice = true;
        } else {
            return $text; // we'll return the text with no changes
        }

        /**********************/

        // Verify if user not authenticated
        $userauth = false;
        if (isguestuser() || $USER == null) {
            $userauth = true;
            header('Location: ' . $CFG->wwwroot . '/login/index.php');
            exit();
        }

        $clientip       = $_SERVER['REMOTE_ADDR'];
        $siyavulaconfig = get_config('filter_siyavula');

        $token = siyavula_get_user_token($siyavulaconfig, $clientip);

        $showbtnretry = $siyavulaconfig->showretry;

        $usertoken = siyavula_get_external_user_token($siyavulaconfig, $clientip, $token);

        // Get user inside in quiz attempt
        $url = $_SERVER["REQUEST_URI"];
        $findme  = '/mod/quiz/attempt.php';
        $pos = strpos($url, $findme);
        $activityid = '';
        $responseid = '';
        $apihtml = '';

        // Get type siyavulaqt
        $comparescaleclause = $DB->sql_compare_text('questiontext')  . ' = ' . $DB->sql_compare_text(':type');
        $typename = $DB->get_record_sql("select * from {question} where $comparescaleclause", array('type' => $text));
        $explodedelimiter = '|';
        // Question type standalone
        if ($syquestion && $pos === false) {
            $newtext = strip_tags($text);

            $globalids = [];

            $re = '/\[{2}[sy\-\d{1,},?|]*\]{2}/m';
            preg_match_all($re, $newtext, $matches);

            // Verify format only sy-
            if (empty($matches[0])) {
                $textclear = str_replace(['sy-'], '', $newtext);
                $ids = explode(',', $textclear);
                $globalids = array_merge($globalids, $ids);
                $issecuencial = true;
            } else {
                if (count($matches[0]) == 1) { // Only found one
                    $issecuencial = true;
                    $newtext = str_replace($matches[0][0], '', $text);
                    // Is secuencial
                    $ids = str_replace(['[[', ']]', 'sy-'], '', $matches[0][0]);
                    $ids = explode(',', $ids);

                    if (count($ids) > 1 /*&& count($newtext) == 0*/) {
                        // If explode give more than one
                        $globalids = array_merge($globalids, $ids); // Then is secuencial, show Next button
                        $withtext = false; // Is secuencial with not text
                    } else {
                        $explodedelimiter = ',';
                        // Only one question, no more
                        $texttoreplacerender = $matches[0][0];
                        $withtext = true; // If only one, maybe have text too
                    }
                } else { // Multiple Questions with texts
                    $issecuencial = false;
                    $withtext = true; // Maybe not have text, but maybe yes
                    foreach ($matches[0] as $match) {
                        $texttoreplacerender[] = $match;
                    }
                }
            }

            $allids = $globalids;

            if ($issecuencial == true && $withtext == true)  // At least, one question with text too
            {

                $nextid = false; // No put the Next button
                $siyavulaactivityid = str_replace(['[[', ']]', 'sy-'], '', $texttoreplacerender); // Only the number
                // If we detext a "," then we will use [0] for the question ID, and [1] for the seed
                $siyavulaactivityid = explode('|', $siyavulaactivityid);

                if (isset($siyavulaactivityid[1])) {
                    $seed = (int) $siyavulaactivityid[1];
                    $siyavulaactivityid = $siyavulaactivityid[0];
                } else {
                    $siyavulaactivityid = $siyavulaactivityid[0];
                }

                $idsq = '';

                $retry = optional_param('changeseed', false, PARAM_BOOL);
                if ($retry) {
                    $seed = rand(1, 99999);
                }

                $externaltoken = $usertoken->token;
                $templateid  = $siyavulaactivityid;
                $randomseed = (isset($seed) ? $seed : rand(1, 99999));
                $baseurl = $siyavulaconfig->url_base;
                $currenturl = $PAGE->URL;

                $questionapi = get_activity_standalone($siyavulaactivityid, $token, $usertoken->token, $siyavulaconfig->url_base, $randomseed);

                if (!empty($questionapi->errors[0])) {
                    $errormsg = $questionapi->errors[0]->code . ' - ' . $questionapi->errors[0]->message;
                    echo ($errormsg);
                }

                if (isset($questionapi->activity->id)) {
                    $activityid  = $questionapi->activity->id;
                }

                if (isset($questionapi->response->id)) {
                    $responseid  = $questionapi->response->id;
                }

                if (isset($questionapi->response->question_html)) {
                    $apihtml = $questionapi->response->question_html;
                }

                $htmlquestion = get_html_question_standalone($apihtml, $activityid, $responseid);
                echo str_replace($texttoreplacerender, $htmlquestion, $text);
                $result = $PAGE->requires->js_call_amd('filter_siyavula/external', 'init', [$baseurl, $token, $externaltoken, $activityid, $responseid, $idsq, $currenturl->__toString(), $nextid, $siyavulaactivityid, $showbtnretry]);
            } else if ($issecuencial == true && $withtext == false) { // Is secuencial, show the "Next" Button

                $sid = optional_param('templateId', false, PARAM_RAW);
                $paramallid = optional_param('all_ids', false, PARAM_RAW);
                $sectionid = optional_param('sectionid', false, PARAM_RAW);
                $currenturl = $PAGE->URL;
                if ($paramallid) {
                    $allids = explode(',', $paramallid);
                    $firstid = $allids[0];

                    // If we detext a "," then we will use [0] for the question ID, and [1] for the seed
                    if (isset($allids[1])) {
                        $seed = (int) $allids[1];
                    }

                    $retry = optional_param('changeseed', false, PARAM_BOOL);
                    if ($retry) {
                        $seed = rand(1, 99999);
                    }

                    $showid = optional_param('show_id', $firstid, PARAM_INT); // The actual show template id is optional, if not get, put the first id found i paral all_ids
                    $siyavulaactivityid = $showid;
                    $nextid = $this->check_if_next($allids, $siyavulaactivityid);

                    $externaltoken = $usertoken->token;
                    $templateid  = $siyavulaactivityid;
                    $baseurl = $siyavulaconfig->url_base;

                    $idsq = implode(',', $allids);
                    $paramseed = explode("|", $siyavulaactivityid);
                    $seed = array_pop($paramseed);

                    $finalidqt = $idsq; // implode('|', $param_seed);

                    $randomseed = (isset($seed) ? $seed : rand(1, 99999));

                    $questionapi = get_activity_standalone($siyavulaactivityid, $token, $usertoken->token, $siyavulaconfig->url_base, $randomseed);
                    $activityid  = $questionapi->activity->id;
                    $responseid  = $questionapi->response->id;

                    if (!empty($questionapi->errors[0])) {
                        $errormsg = $questionapi->errors[0]->code . ' - ' . $questionapi->errors[0]->message;
                        echo ($errormsg);
                    }

                    $htmlquestion = get_html_question_standalone_sequencial($questionapi->response->question_html, $activityid, $responseid);
                    $text = str_replace($matches[0][0], '{{}}', $text);

                    echo str_replace('{{}}', $htmlquestion, $text);
                    $result = $PAGE->requires->js_call_amd('filter_siyavula/external', 'init', [$baseurl, $token, $externaltoken, $activityid, $responseid, $finalidqt, $currenturl->__toString(), $nextid, $siyavulaactivityid, $showbtnretry]);
                } else {

                    // Check if questions contain param seed, param seed is used un one or multiple questions
                    $finishids = array();
                    foreach ($allids as $aid) {
                        $nextidcheck = explode('|', $aid);
                        unset($nextidcheck[1]);

                        foreach ($nextidcheck as $check) {
                            $finishids[] = $check;
                        }
                    }

                    $idsq = implode(',', $allids);

                    $findme   = ',';
                    $pos = strpos($idsq, $findme);

                    if ($pos) {

                        $indexseed = explode(',', $idsq);
                        foreach ($indexseed as $sd) {

                            $paramseparator = explode("|", $sd);

                            $idquestion = $paramseparator[0];
                            $randomseed = $paramseparator[1];

                            $nextid = $this->check_if_next($finishids, $idquestion);

                            $idsallnext = implode(',', $finishids);

                            $questionapi = get_activity_standalone($idquestion, $token, $usertoken->token, $siyavulaconfig->url_base, $randomseed);

                            if (!empty($questionapi->errors[0])) {
                                $errormsg = $questionapi->errors[0]->code . ' - ' . $questionapi->errors[0]->message;
                                echo ($errormsg);
                            }

                            $activityid  = $questionapi->activity->id;
                            $responseid  = $questionapi->response->id;
                            $externaltoken = $usertoken->token;
                            $baseurl = $siyavulaconfig->url_base;

                            $htmlquestion = get_html_question_standalone_sequencial($questionapi->response->question_html, $activityid, $responseid);

                            $text = str_replace($matches[0][0], '{{}}', $text);

                            echo str_replace('{{}}', $htmlquestion, $text);
                            $result = $PAGE->requires->js_call_amd('filter_siyavula/external', 'init', [$baseurl, $token, $externaltoken, $activityid, $responseid, $idsallnext, $currenturl->__toString(), $nextid, $idquestion, $showbtnretry]);
                            break;
                        }
                    }
                }
            } else if ($issecuencial == false && $withtext == true) // Is not secuencial, multiple questions and maybe have text
            {
                $toecho = $text;
                foreach ($texttoreplacerender as $ttrr) {
                    $idsq = '';
                    $nextid = false; // No put the Next button
                    $siyavulaactivityid = str_replace(['[[', ']]', 'sy-'], '', $ttrr); // Only the number

                    // If we detext a "," then we will use [0] for the question ID, and [1] for the seed
                    $siyavulaactivityid = explode('|', $siyavulaactivityid);

                    if (isset($siyavulaactivityid[1])) {
                        $seed = (int) $siyavulaactivityid[1];
                        $siyavulaactivityid = $siyavulaactivityid[0];
                    } else {
                        $siyavulaactivityid = $siyavulaactivityid[0];
                    }

                    $retry = optional_param('changeseed', false, PARAM_BOOL);
                    if ($retry) {
                        $seed = rand(1, 99999);
                    }

                    $externaltoken = $usertoken->token;
                    $activitytype = 'standalone';
                    $templateid  = $siyavulaactivityid;
                    $randomseed = (isset($seed) ? $seed : rand(1, 99999));
                    $baseurl = $siyavulaconfig->url_base;
                    $currenturl = $PAGE->URL;

                    unset($seed);

                    $questionapi = get_activity_standalone($siyavulaactivityid, $token, $usertoken->token, $siyavulaconfig->url_base, $randomseed);

                    if (!empty($questionapi->errors[0])) {
                        $errormsg = $questionapi->errors[0]->code . ' - ' . $questionapi->errors[0]->message;
                        echo ($errormsg);
                    }

                    $activityid  = $questionapi->activity->id;
                    $responseid  = $questionapi->response->id;

                    $htmlquestion = get_html_question_standalone($questionapi->response->question_html, $activityid, $responseid);

                    $toecho = str_replace($ttrr, $htmlquestion, $toecho);
                }
                echo $toecho;
                $result = $PAGE->requires->js_call_amd('filter_siyavula/external', 'init', [$baseurl, $token, $externaltoken, $activityid, $responseid, $idsq, $currenturl->__toString(), $nextid, $siyavulaactivityid, $showbtnretry]);
            }
        }

        // Question type practice
        if (!$userauth && $qtpractice) {

            $newtext = strip_tags($text);
            $re = '/\[{2}[syp\-\d{1,},?|]*\]{2}/m';

            preg_match_all($re, $newtext, $matches);
            $globalids = [];
            foreach ($matches[0] as $match) {
                $siyavulaactivityid = str_replace(['[[', ']]', 'syp-'], '', $match);
                $baseurl = $siyavulaconfig->url_base;

                // If we detext a "," then we will use [0] for the question ID, and [1] for the seed
                $siyavulaactivityid = explode('|', $siyavulaactivityid);

                if (isset($siyavulaactivityid[1])) {
                    $seed = (int) $siyavulaactivityid[1];
                    $siyavulaactivityid = $siyavulaactivityid[0];
                } else {
                    $siyavulaactivityid = $siyavulaactivityid[0];
                }

                // Check if retry question is send params
                $aid        = optional_param('aid', null, PARAM_RAW);
                $rid        = optional_param('rid', null, PARAM_RAW);
                if ($aid != null  && $rid != NUL) {
                    $retryhtml = retry_question_html_practice($aid, $rid, $token, $usertoken->token, $baseurl);

                    $questionapi = get_activity_practice_toc($siyavulaactivityid, $token, $usertoken->token, $baseurl);

                    $activityid  = $retryhtml->activity->id;
                    $responseid  = $retryhtml->response->id;
                    $idqt   = $retryhtml->practice->section->id;
                    $seedqt = $retryhtml->response->random_seed;

                    $htmlpractice = get_html_question_practice($retryhtml->response->question_html, $questionapi->practice->chapter->title, $questionapi->practice->chapter->mastery, $questionapi->practice->section->title, $questionapi->practice->section->mastery);
                    echo $htmlpractice;
                    $PAGE->requires->js_call_amd('filter_siyavula/externalpractice', 'init', [$baseurl, $token, $usertoken->token, $activityid, $responseid, $showbtnretry, $idqt, $seedqt]);
                } else {
                    $externaltoken = $usertoken->token;
                    if (isset($seed)) {
                        $randomseed = $seed;
                    } else {
                        $randomseed = rand(1, 99999);
                    }

                    $questionapi = get_activity_practice($siyavulaactivityid, $token, $usertoken->token, $baseurl, $randomseed);

                    if (!empty($questionapi->errors[0])) {
                        $errormsg = $questionapi->errors[0]->code . ' - ' . $questionapi->errors[0]->message;
                        echo ($errormsg);
                    }

                    $activityid  = $questionapi->activity->id;
                    $responseid  = $questionapi->response->id;
                    $idqt   = $questionapi->practice->section->id;
                    $seedqt = $questionapi->response->random_seed;

                    $htmlpractice = get_html_question_practice($questionapi->response->question_html, $questionapi->practice->chapter->title, $questionapi->practice->chapter->mastery, $questionapi->practice->section->title, $questionapi->practice->section->mastery);
                    echo $htmlpractice;
                    $result = $PAGE->requires->js_call_amd('filter_siyavula/externalpractice', 'init', [$baseurl, $token, $externaltoken, $activityid, $responseid, $showbtnretry, $idqt, $seedqt]);

                    break;
                }
            }
        }

        // Render questions not apply format siyavula
        if (!empty($result)) {
            return $result;
        } else {
            return $text;
        }
    }
}
