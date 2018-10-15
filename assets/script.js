/**
 * @author Alexey Petrov
 */

// Script initialization
$(document).ready(function() {
    $('#myForm').submit(function() {
        MyForm.submit();
        return false;
    });
});

/**
 * Object that supports form work
 */
let MyForm = {
    /**
     * @returns Object object with validation result
     */
    validate : function() {
        // Validation rules
        let rules = {
            // csv file rule
            import_data : function(value) {
                let result = false;
                // csv extension
                let re = /\.csv$/;
                if (re.test(value)) {
                    result = true;
                }
                return result;
            },
        }
        // Fields validating
        let result = {
            isValid : true,
            errorFields : []
        }
        // Look over all input fields
        $('#myForm input').each(function() {
            let name = $(this).attr('name');

            if (rules[name]) {
                // Have rule to validate
                if (rules[name]($(this).val())) {
                    return true;
                }
                result.isValid = false;
                result.errorFields.push(name);
            }
        });
        return result;
    },

    /**
     * @returns FormData object with form data
     */
    getData : function() {
        let form_data = new FormData();
        
        $('#myForm input').each(function() {
            
            if ($(this).attr('type') == 'file') {
                // Get file field
                form_data.append($(this).attr('name'), $(this).prop('files')[0]);
            } else {
                // Get text field
                form_data.append($(this).attr('name'), $(this).val());
            }
        });
        
        return form_data;
    },

    /**
     * Performs field validation and sends an ajax request
     */
    submit : function() {
        // Remove all errors alerts anyway
        $('#myForm input').each(function() {
            $(this).removeClass();
        });
        $('#resultContainer').removeClass().empty();
        // Validate the form
        let v_result = this.validate();

        if (!v_result.isValid) {

            $.each(v_result.errorFields, function(key, field_name) {
                $('#myForm input[name = ' + field_name + ']').addClass('error');
            });
            return;
        }
        $('#myForm button[type=submit]').attr('disabled', 'disabled');
        // Process request
        let form_ajax = {
            send : function() {
                $.ajax({
                    url : $('#myForm').attr('action'),
                    type : 'POST',
                    data : MyForm.getData(),
                    cache : false,
                    dataType : 'text',
                    processData: false,
                    contentType: false,
                }).then(function(result) {
                    result = JSON.parse(result);
                    let unlock_button = true;
                    $('#resultContainer').removeClass();

                    if ('status' in result) {
                        // Here we get the result
                        if (result.status == 'success') {
                            $('#resultContainer').addClass('success');
                            
                            $('#resultContainer').append($('<p>Обновлено артикулов: ' + result.updated + '</p>'));
                            $('#resultContainer').append($('<p>Добавлено артикулов: ' + result.inserted + '</p>'));
                            $('#resultContainer').append($('<p>Не хватает артикулов: ' + result.missing + '</p>'));
                            $('#resultContainer').append($('<p>Обработано строк в файле: ' + result.processed + '</p>'));
                        } else if (result.status == 'error') {
                            $('#resultContainer').addClass('error').text(result.reason);
                        }
                    } else {
                        // Something is wrong
                        $('#resultContainer').addClass('error').text('Unexpected response from the server');
                    }
                    // Unlock submit button
                    if(unlock_button) {
                        $('#myForm button[type=submit]').removeAttr('disabled');
                    }
                }, function() {
                    // Something is wrong
                    $('#resultContainer').addClass('error').text('Request failed');
                    $('#myForm button[type=submit]').removeAttr('disabled');
                });
            }
        }
        form_ajax.send();
    }
};