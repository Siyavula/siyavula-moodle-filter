define(['jquery','core/ajax'], function ($,Ajax) {
          return {
            init: function(baseurl,token,external_token,activityid,responseid,show_retry_btn) {
           
                $(document).ready(function () {
                    show_retry_btn = parseInt(show_retry_btn)
                    $("p:contains('syp-')").css("display", "none");
                    
                    $(document).on('click','.sv-button.sv-button--primary.check-answer-button',function(e){
                        e.preventDefault();
                        var formData = $('form[name="questions"]').serialize()
                        var submitresponse = Ajax.call(
                        [{ 
                            methodname: 'filter_siyavula_submit_answers_siyavula', 
                            args: { 
                                baseurl: baseurl,
                                token: token,
                                external_token: external_token,
                                activityid: activityid,
                                responseid: responseid,
                                data:  formData,
                            }
                        }]);
                        submitresponse[0].done(function (response) {
                            var dataresponse = JSON.parse(response.response);
                            var html = dataresponse.response.question_html
                            $('.question-content').html(html);    
                            $('.toggle-solution-checkbox').css("display", "none");
                            $('#nav-buttons .sv-button--goto-question').css("display","none")
                            $(".toggle-solution-checkbox").attr("data-show",false);
                            
                            const retry = document.querySelector('a[name="retry"]')
                            if(retry){
                              retry.setAttribute('href',location.href+(location.href.includes('?')?'&':'?')+'changeseed=true');
                              console.log('show_retry_btn: ', show_retry_btn)
                              if(!show_retry_btn) {
                                  // Hide the btn
                                  console.log('hide');
                                  retry.style.display = 'none';
                              }
                            }
                            
                            var feedback = $(".response-query-body").find(".feedback--incorrect");
                            
                            if(feedback.length === 1){
                                $("span:contains('Show the full solution')").css("display", "none");
                                var show = $("span:contains('Show the full solution')").css('display').toLowerCase() == 'none'
                            }else{
                                $("span:contains('Hide the full solution')").css("display", "none");
                            }
                            
                            $('.toggle-solution-checkbox').on('click',function(e){
                                const eventhide = e.target.attributes.id.value
                                
                                if (show === true){
                                     $(`label[for="${eventhide}"]>span:contains('Show the full solution')`).css("display", "block");
                                     $(`label[for="${eventhide}"]>span:contains('Hide the full solution')`).css("display", "none");
                                     show = false
                                }else{
                                     $(`label[for="${eventhide}"]>span:contains('Show the full solution')`).css("display", "none");
                                     $(`label[for="${eventhide}"]>span:contains('Hide the full solution')`).css("display", "block");
                                     show = true
                                } 
                                $(`label[for="${eventhide}"]+.response-solution`).slideToggle();
                            });
            
                        }).fail(function (ex) {
                            console.log(ex);
                        });
                    });
                });
            }
        };
    });