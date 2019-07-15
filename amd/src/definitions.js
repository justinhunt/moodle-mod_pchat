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
        C_UPDATECONTROL: 'customtext1',
    };//end of return value
});