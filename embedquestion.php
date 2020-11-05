<?php
require_once('../../../config.php');
require_once($CFG->dirroot.'/filter/siyavula/lib.php');

$siyavula_activity_id = required_param('qid', PARAM_RAW);

$client_ip = $_SERVER['REMOTE_ADDR'];
$siyavula_config = get_config('filter_siyavula');
$token = siyavula_get_user_token($siyavula_config, $client_ip);
$user_token = siyavula_get_external_user_token($siyavula_config, $client_ip, $token);

if(!isset($user_token->token)){
  redirect(new moodle_url('/question/type/siyavulaqt/embedquestion.php', array('qid' => $siyavula_activity_id)));
}
?>
<link rel="stylesheet" href="<?php echo $siyavula_config->url_base; ?>/static/themes/emas/siyavula-api/siyavula-api.min.css"/>
<link rel="stylesheet" href="<?php echo $siyavula_config->url_base; ?>/themes/emas/question-api/question-api.min.css"/>
<script>
    var baseUrl = '<?php echo $siyavula_config->url_base; ?>';
    var token = '<?php echo $token; ?>';
    var userToken = "<?php echo $user_token->token; ?>";
    var activityType = "standalone";
    var templateId = <?php echo $siyavula_activity_id; ?>;
    var randomSeed = 3527;
</script>
<main class="sv-region-main emas sv">
  <div id="monassis" class="monassis monassis--practice monassis--maths monassis--siyavula-api">
    <div class="question-wrapper">
      <div class="question-content"></div>
    </div>
  </div>
</main>
<script src="<?php echo $siyavula_config->url_base; ?>/static/themes/emas/node_modules/mathjax/MathJax.js?config=TeX-MML-AM_HTMLorMML-full"></script>
<script src="<?php echo $siyavula_config->url_base; ?>/static/themes/emas/siyavula-api/siyavula-api.js"></script>