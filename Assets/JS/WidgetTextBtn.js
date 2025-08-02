$(document).on('click', '.widgetTextBtn button', function() {
    let input = $(this).closest('.widgetTextBtn').find('input');

    let value = $(input).val();
    let jsfile = $(input).attr('jsfile');
    let action = $(input).attr('action');

    if (action === '' || value === '') {
        return;
    }

    if (jsfile === '') {
        jsfile = window.location.href;
    }

    $.ajax({
        method: 'POST',
        url: jsfile,
        data: {
            'value': value,
            'action': action,
        },
        dataType: 'json',
        success: function (results) {
            if (results.success && results.value !== '') {
                $(input).val(results.value);
            }

            if (results.message) {
                alert(results.message)
            }
        },
        error: function(msg) {
            alert(msg.status + ' ' + msg.responseText);
        }
    })
});