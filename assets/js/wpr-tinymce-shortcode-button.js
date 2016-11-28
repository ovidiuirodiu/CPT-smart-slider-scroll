(function () {
    tinymce.PluginManager.add('wpr_add_shortcode_button', function (editor, url) {
        editor.addButton('wpr_add_shortcode_button', {
            title: 'Add post slider',
            icon: 'wp_code',
            onclick: function () {
                // Open window
                editor.windowManager.open({
                    title: 'Add shortcode post slider',
                    body: [{
                        type: 'listbox',
                        name: 'wprcategory',
                        label: 'Select slider',
                        values: WPR_getValues()
                    }, {
                        type: 'listbox',
                        name: 'postsnumber',
                        label: 'Slides initial number',
                        'values': [
                            {text: '3', value: '3'},
                            {text: '4', value: '4'},
                            {text: '5', value: '5'},
                            {text: '6', value: '6'}
                        ]
                    }],
                    onsubmit: function (e) {
                        // Insert content when the window form is submitted
                        editor.insertContent('[wpr-slides wpr_slider="' + e.data.wprcategory + '" wpr_default="' + e.data.postsnumber + '"]');
                    },
                    width: 700,
                    height: 200
                });
            }
        });
    });

})();