export const init = (chktrue, chkfalse) => {
    console.log(chktrue);
    console.log(chkfalse);
    
    //a^14
    
    var timer = window.setInterval(() => {
        var height = $('#siyavulaQContainer').contents().find('.sv-region-main').height();
        
        $('#siyavulaQContainer').height(height + 40);
        
        if($('#siyavulaQContainer').contents().find('.feedback--incorrect').length > 0){
            $('[for="'+chkfalse.id+'"]').click();
        }
        if($('#siyavulaQContainer').contents().find('.feedback--correct').length > 0){
            $('[for="'+chktrue.id+'"]').click();
        }
    }, 1000);
};