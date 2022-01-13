define(['jquery','core/ajax'], function ($,Ajax) {
          return {
            init: function(baseurl,token,external_token,activityid,responseid,idsq,currenturl,next_id,$siyavula_activity_id,show_retry_btn) {
           
                $(document).ready(function () {
                    show_retry_btn = parseInt(show_retry_btn)
                    $('.question-content').on('click',function(e){
                        const response = e.currentTarget.dataset.response
                        const targetid = e.currentTarget.id
                        if(e.target.className === 'sv-button sv-button--primary check-answer-button'){
                            e.preventDefault();
                            var formData = $(`div#${targetid} form[name="questions"]`).serialize()
                            var submitresponse = Ajax.call(
                            [{ 
                                methodname: 'filter_siyavula_submit_answers_siyavula', 
                                args: { 
                                    baseurl: baseurl,
                                    token: token,
                                    external_token: external_token,
                                    activityid: targetid,
                                    responseid: response,
                                    data:  formData,
                                }
                            }]);
                            submitresponse[0].done(function (response) {
                                var dataresponse = JSON.parse(response.response);
                                var html = dataresponse.response.question_html
                                let timest = Math.floor(Date.now() / 1000);
                                html = html.replaceAll('sv-button toggle-solution', `sv-button toggle-solution btnsolution-${targetid}-${timest}`);
                                $(`#${targetid}.question-content`).html(html);    
                                $(`div#${targetid} .toggle-solution-checkbox`).css("visibility", "hidden");
                                
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
                                
                                const theId = targetid;
                                const escapeID = CSS.escape(theId)
   
                                const labelsSolution = document.querySelectorAll(`#${escapeID}.question-content .btnsolution-${escapeID}-${timest}`);

                                labelsSolution.forEach(labelSolution => {
                                    labelSolution.innerHTML = '';
                                    var btntarget = labelSolution.getAttribute('for')
                                    const currentTargeId = btntarget.replace('toggle-', '');
     
                                    const newShowSpan = document.createElement('span')
                                    newShowSpan.append('Show the full solution');
                                    newShowSpan.id = 'show';
                                    
                                    const newHideSpan = document.createElement('span')
                                    newHideSpan.append('Hide the full solution');
                                    newHideSpan.id = 'hide';
                                    
                                    const response_solution = document.querySelectorAll(`#${escapeID}.question-content .response-solution`);

                                    var is_correct = true;
                                    const rsElement = response_solution[currentTargeId]

                                    if(rsElement.id == 'correct-solution') {
                                        is_correct = true;
                                    }
                                    else {
                                        is_correct = false;
                                    }
                                     
                                    if(is_correct == false){
                                        //$(`div#${targetid} span:contains('Show the full solution')`).css("display", "none");
                                        newShowSpan.style.display = 'none';
                                    }else{
                                        //$(`div#${targetid} span:contains('Hide the full solution')`).css("display", "none");
                                        newHideSpan.style.display = 'none';
                                    }
                                    labelSolution.append(newShowSpan);
                                    labelSolution.append(newHideSpan);

                                    $(`div#${targetid} .sv-button--goto-question`).css("display","none")
                                    
                                    const spanShow = labelSolution.querySelector("span#show");
                                    const spanHide = labelSolution.querySelector("span#hide");
                                    const functionClickSolution = btnE => {
                                        const currentSpan = btnE.target;
                                        if(currentSpan.innerHTML.includes('Show')) {
                                            spanShow.style.display = 'none';
                                            spanHide.style.display = 'inherit';
                                        }
                                        else {
                                            spanShow.style.display = 'inherit';
                                            spanHide.style.display = 'none';
                                        }
                                        
                                        $(`div#${targetid} label[for="${btntarget}"]+.response-solution`).slideToggle();
                                        
                                    }
                                    spanShow.addEventListener('click', functionClickSolution);
                                    spanHide.addEventListener('click', functionClickSolution);
                                })

                            }).fail(function (ex) {
                                console.log(ex);
                            });
                        }
                        
                    })
                    
                    $("p:contains('sy-')").css("display", "none");
                    if($("#qt")[0]) {
                        $("#qt")[0].nextSibling.remove()
                    }
                    
                    function checkQuestion(){
                        var id    =  activityid;
                        var param =  idsq;
                        var next  =  next_id;
                        
                        var btn = document.querySelector('#a_next')
                        
                        if(btn){
                            btn.href = `${currenturl}?templateId=${$siyavula_activity_id}&all_ids=${param}&show_id=${next}`
                            if(next == false){
                                btn.innerHTML = '';
                            }
                        }
                    }
                    checkQuestion()
                });
            }
        };
    });
