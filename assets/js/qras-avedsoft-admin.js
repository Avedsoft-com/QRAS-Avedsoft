jQuery(document).ready(function($) {
    // Function to update UI based on QR code presence
    function updateUI() {
        var src = $('#qras_code').attr('src');
        var checkboxVisible = $('#qras_code_access').length > 0;

        if (src && src !== '') {
            $('#create_qras_code').hide();
            $('.qras-code-download-link').show();
            $('#qras_code').show();
            if (checkboxVisible) {
                $('.qras-code-access-control').show();
            }
        } else {
            $('#create_qras_code').show();
            $('.qras-code-download-link').hide();
            $('#qras_code').hide();
            $('.qras-code-access-control').hide();
        }
    }

    // Function to fetch existing QR code if available
    function fetchExistingQRCode() {
        var post_id = $('#post_id').val();
        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_saved_qr_code_url',
                post_id: post_id,
                security: ajax_object.security
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    $('#qras_code').attr('src', response.data.url).show();
                    $('.qras-code-download-link').html('<a href="' + response.data.url + '" target="_blank">' + ajax_object.messages.downloadPNG + '</a><button id="delete_qras_code" class="button button-secondary">' + ajax_object.messages.delete + '</button>').show();
                    $('#qras_code_access').prop('checked', response.data.access === '1');
                } else {
                    $('#qras_code').hide();
                    $('.qras-code-download-link').hide();
                    $('#qras_code_access').prop('checked', false);
                }
                updateUI();
            },
            error: function(xhr, status, error) {
                console.error('Error fetching existing QR code:', error);
                $('#qras_code').hide();
                $('.qras-code-download-link').hide();
                $('#qras_code_access').prop('checked', false);
                console.error('Failed to fetch QR code:', error);
            }
        });
    }


    // Attach event handler for 'Create QR Code' button click
    $('#create_qras_code').on('click', function(e) {
        e.preventDefault();  // Prevent default form submission
        var post_id = $('#post_id').val();  // Retrieve the post ID from the input field

        // Perform AJAX POST request
        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_qr_code',
                post_id: post_id,
                security: ajax_object.security
            },
            success: function(response) {
                if (response.success) {
                    // Display QR code and manage UI elements
                    $('#qras_code').attr('src', response.data.url).show();
                    $('.qras-code-download-link').html('<a href="' + response.data.url + '" target="_blank">' + ajax_object.messages.downloadPNG + '</a><button id="delete_qras_code" class="button button-secondary">' + ajax_object.messages.delete + '</button>').show();
                    updateUI();  // Update UI based on response
                    alert(ajax_object.messages.qrGenerated);
                } else {
                    // Handle failure by displaying server-provided message
                    alert(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX errors
                console.error('Failed to generate QR code:', error);
                alert(ajax_object.messages.qrGenerateError);
            }
        });
    });



    // Event handler for 'Delete QR Code' button
    $(document).on('click', '#delete_qras_code', function(e) {
        e.preventDefault();
        var post_id = $('#post_id').val();
        if (confirm(ajax_object.messages.deleteConfirm)) {
            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_qr_code',
                    post_id: post_id,
                    security: ajax_object.security
                },
                success: function(response) {
                    if (response.success) {
                        // Clear and hide QR code elements upon successful deletion
                        $('#qras_code').attr('src', '').hide();
                        $('.qras-code-download-link').empty().hide();
                        $('#qras_code_access').prop('checked', false);
                        updateUI();
                        alert(ajax_object.messages.qrDeleted);
                    } else {
                        // Display error message if deletion fails
                        alert(response.data.message);
                    }
                },
                error: function() {
                    // Alert the user if an AJAX error occurs
                    alert(ajax_object.messages.qrDeleteError);
                }
            });
        }
    });



    // Handle change event for the QR code access checkbox
    $(document).on('change', '#qras_code_access', function(e) {
        var access = $(this).is(':checked') ? '1' : '0';
        var post_id = $('#post_id').val();
        if (confirm(ajax_object.messages.confirmAccessChange)) {
            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'qras_toggle_access',
                    post_id: post_id,
                    access: access,
                    security: ajax_object.security
                },
                success: function(response) {
                    if (response.success) {
                        updateUI();  // Refresh the UI to reflect the change
                        alert(ajax_object.messages.accessUpdated);  // Notify the user of success
                    } else {
                        alert(ajax_object.messages.accessUpdateFailed);  // Notify the user of failure
                    }
                },
                error: function() {
                    alert('Failed to update access settings.');  // Notify the user of AJAX error
                }
            });
        } else {
            // Revert checkbox state if the user cancels the confirmation
            $(this).prop('checked', access === '0');  // Fix logical issue with checkbox revert
        }
    });


    // Fetch existing QR code on page load
    fetchExistingQRCode();
});
