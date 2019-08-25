define(['jquery','core/log','mod_pchat/definitions', 'core/notification'], function($,log, def, notification) {
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
                    var targetwords =selectedtopic.targetwords;
                    $('[name="' + update + '"]').val(targetwords);
                }
            });
        }

};//end of return value

});

