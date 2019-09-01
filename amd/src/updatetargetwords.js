define(['jquery','core/log','mod_pchat/definitions', 'core/notification', 'core/templates'], function($,log, def, notification,templates) {
    "use strict"; // jshint ;_;

/*
This file contains class and ID definitions.
 */

    log.debug('PChat Update Target Words: initialising');

    return{

        init: function(opts) {
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
                }
            });
        }

};//end of return value

});

