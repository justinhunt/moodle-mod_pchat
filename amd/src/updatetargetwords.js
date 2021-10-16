define(['jquery','core/log','mod_pchat/definitions', 'core/notification', 'core/templates'], function($,log, def, notification,templates) {
    "use strict"; // jshint ;_;

/*
This file contains class and ID definitions.
 */

    log.debug('PChat Update Target Words: initialising');

    return{

        init: function(opts) {
            //pick up opts from html
            var theid = '#amdopts_' + opts.controlid;
            var configcontrol = $(theid).get(0);
            if (configcontrol) {
                opts = JSON.parse(configcontrol.value);
                $(theid).remove();
            } else {
                //if there is no config we might as well give up
                log.debug(' Controller: No config found on page. Giving up.');
                return;
            }

            this.register_events(opts['topics'],opts['triggercontrol'], opts['updatecontrol']);
        },

        register_events: function(topics,trigger,update){
            $('[name="' + trigger + '"]').on('change',function(){
                var newvalue = $(this).val();
                var selectedtopic=false;
                $.each(topics,function(){
                    if(this.id==newvalue){
                        selectedtopic=this;
                    }
                });
                if(selectedtopic) {
                    $('[name="' + update + '"]').val(selectedtopic.targetwords);
                    if(selectedtopic.targetwords.trim()===''){return;}
                    var tdata=[];
                    tdata['targetwords'] =selectedtopic.targetwords.split('\n');
                    templates.render('mod_pchat/targetwords',tdata).then(
                        function(html,js){
                            var d= $('#' + def.C_TARGETWORDSDISPLAY);
                            d.html(html);
                        }
                    );
                    //lets also hide the red warning message
                    $('.mod_pchat_topiccombo_explainer').hide();
                }
            });
        }

};//end of return value

});

