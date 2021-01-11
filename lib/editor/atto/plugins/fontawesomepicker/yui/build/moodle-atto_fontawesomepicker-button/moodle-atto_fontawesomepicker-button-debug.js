YUI.add('moodle-atto_fontawesomepicker-button', function (Y, NAME) {

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


/**
 * @module moodle-atto_fontawesomepicker-button
 */

/**
 * Atto text editor fontawesomepicker plugin.
 *
 * @namespace M.atto_fontawesomepicker
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

Y.namespace('M.atto_fontawesomepicker').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    initializer: function () {
        var icons = this.get('icons');
        var items = [];
        var cpt = 0;
        Y.Array.each(icons, function (icon) {
            if(cpt < 20 ){
                items.push({
                    text: '<i class="' + icon + ' fa-2x" aria-hidden="true"></i>',
                    callbackArgs: icon
                });
                cpt++;
            }
        });

        for (var i = 0; i < items.length % 3 ; i++) {
            items.push({
                text: '',
                callbackArgs: null
            });
        }

        this.addToolbarMenu({
            icon: 'ed/font-awesome-brands',
            iconComponent: 'atto_fontawesomepicker',
            overlayWidth: '4',
            globalItemConfig: {

                callback: this._addfontawesomeicon
            },
            items: items
        });
    },

    /**
     * Add Icon
     *
     * @method _changeStyle
     * @param {EventFacade} e
     * @param {string} color The new background color
     * @private
     */
    _addfontawesomeicon: function (e, icon) {
        if(icon){

            document.execCommand('insertText', false, "[" + icon.replace('fa ', '') + " fa-pull-left fa-2x]");

            // Mark as updated
            this.markUpdated();
        }

    }

}, {
    ATTRS: {

        icons: {
            value: {}
        }
    }
});

}, '@VERSION@');
