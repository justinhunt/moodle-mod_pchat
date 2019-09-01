define(['jquery','core/log'], function($,log) {
    "use strict"; // jshint ;_;

/*
This file contains class and ID definitions.
 */

    log.debug('PChat definitions: initialising');

    return{
        component: 'mod_pchat',
        C_AUDIOPLAYER: 'vs_audioplayer',
        C_CURRENTFORMAT: 'vs_currentformat',
        C_KEYFORMAT: 'vs_keyformat',
        C_ATTRFORMAT: 'vs_attrformat',
        C_FILENAMETEXT: 'vs_filenametext',
        C_UPDATECONTROL: 'filename',
        topicscontainer: 'topicscontainer',
        topiccheckbox: 'topicscheckbox',
        C_BUTTONAPPLY: 'poodllsubtitle_edapply',
        C_BUTTONDELETE: 'poodllsubtitle_eddelete',
        C_BUTTONMOVEUP: 'poodllsubtitle_edmoveup',
        C_BUTTONMOVEDOWN: 'poodllsubtitle_edmovedown',
        C_BUTTONCANCEL: 'poodllsubtitle_edcancel',
        C_EDITFIELD: 'poodllsubtitle_edpart',
        C_TARGETWORDSDISPLAY: 'mod_pchat_targetwordsdisplay'


    };//end of return value
});