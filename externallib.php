<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class filter_siyavula_external extends external_api {
    
    public static function submit_answer_parameters() {
        return new external_function_parameters(
            array(
                'baseurl'            => new external_value(PARAM_URL, 'Url base siyavula'),
                'token'              => new external_value(PARAM_RAW, ''),
                'external_token'     => new external_value(PARAM_RAW, ''),
                'activityid'         => new external_value(PARAM_RAW, ''),
                'responseid'         => new external_value(PARAM_RAW, ''),
                'data'               => new external_value(PARAM_TEXT, ''),
            )
        );
    }
    
    /**
     * Function get courses in tgas relations, event gallery for webservice.
     * @return external_function_parameters
     */
    public static function submit_answer($baseurl,$token,$external_token,$activityid,$responseid,$data) {

        $payload = $data;
    
        $curl = curl_init();
 
        curl_setopt_array($curl, array(
          CURLOPT_URL => $baseurl.'api/siyavula/v1/activity/'.$activityid.'/response/'.$responseid.'/submit-answer',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $payload,
          CURLOPT_HTTPHEADER => array('JWT: ' .$token, 'Authorization: JWT ' .$external_token),
        ));
        $response = curl_exec($curl);
        $response = json_decode($response);
        $question_html = $response->response->question_html;
        $new_question_html = '';
        $new_question_html .= '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/MathJax.js?id=2&config=TeX-MML-AM_HTMLorMML-full"></script>'; // Para cargar el MathJax
        // $new_question_html .= '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/jax/output/HTML-CSS/fonts/TeX/fontdata.js?V=2.7.5"></script>';
        //$new_question_html .= '<script src="https://www.siyavula.com/static/themes/emas/node_modules/mathjax/config/TeX-MML-AM_HTMLorMML-full.js?V=2.7.5"></script>';
        $new_question_html .= $question_html;
        
        $response->response->question_html = $new_question_html;
        $response = json_encode($response);

        curl_close($curl);
    
        return array('response' => $response);
    }
    
    /**
     * Return info data tags and course info
     * @return tag_courses_returns
     */
    public static function submit_answer_returns(){
        return new external_single_structure(
            array(
                'response' => new external_value(PARAM_RAW, '')
            )
        );
    }
}