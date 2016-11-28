function WPR_getValues() {
    var data = {'action': 'wpr_get_sliders'};

    var q = jQuery.ajax({
        type: 'POST',
        url: ajax_backend.ajax_url,
        data: data,
        async: false,
        dataType: 'json'
    });

    var values = q.responseJSON;
    return values;
}