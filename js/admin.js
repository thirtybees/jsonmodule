function initializeFileUpload(i) {
    $('#founder_picture_' + i + '-selectbutton').click(function(e) {
        $('#founder_picture_' + i).trigger('click');
    });
    $('#founder_picture_' + i + '-name').click(function(e) {
        $('#founder_picture_' + i).trigger('click');
    });
    $('#founder_picture_' + i + '-name').on('dragenter', function(e) {
        e.stopPropagation();
        e.preventDefault();
    });
    $('#founder_picture_' + i + '-name').on('dragover', function(e) {
        e.stopPropagation();
        e.preventDefault();
    });
    $('#founder_picture_' + i + '-name').on('drop', function(e) {
        e.preventDefault();
        var files = e.originalEvent.dataTransfer.files;
        $('#founder_picture_' + i)[0].files = files;
        $(this).val(files[0].name);
    });
    $('#founder_picture_' + i).change(function(e) {
        if ($(this)[0].files !== undefined)
        {
            var files = $(this)[0].files;
            var name  = '';

            $.each(files, function(index, value) {
                name += value.name+', ';
            });

            $('#founder_picture_' + i + '-name').val(name.slice(0, -2));
        }
        else // Internet Explorer 9 Compatibility
        {
            var name = $(this).val().split(/[\\/]/);
            $('#founder_picture_' + i + '-name').val(name[name.length-1]);
        }
    });
    $('#founder_picture_' + i).val('');
    $('#founder_picture_' + i + '-name').val('');
}
$(document).ready(function () {
    $('#founder_picture_0').addClass('founder-field-0'); // fix class name of file upload field to have it selected with others
    $('#btn-add-founder').click(function () { // add founder button
        var total = $('.founder-number').length; // determine the order of the new group
        var fields = [];
        $('.founder-field-0').each(function () {
            var aWrapper = $(this).parent().parent().clone(); // clone the field with its wrapper
            // if it's a file field, it has two more wrappers
            if (typeof $(this).attr('type') !== 'undefined' && $(this).attr('type') === 'file') {
                aWrapper = $(this).parent().parent().parent().parent().clone();
            }
            // //
            // loop through elements in the wrapper to rename id and name attributes
            aWrapper.find('*').each(function() {
                var aField = $(this);
                var fieldId = $(this).attr('id');
                if (typeof fieldId !== 'undefined') {
                    fieldId = fieldId.replace('_0', '_' + total);
                }
                $(aField).attr('id', fieldId);
                var fieldClass = $(this).attr('class');
                if (typeof fieldClass !== 'undefined') {
                    fieldClass = fieldClass.replace('-0', '-' + total);
                }
                $(aField).attr('class', fieldClass);
                var fieldName = $(this).attr('name');
                if (typeof fieldName !== 'undefined') {
                    fieldName = fieldName.replace('_0', '_' + total);
                }
                $(aField).attr('name', fieldName);
                if (aField.hasClass('founder-number')) {
                    aField.text(total + 1);
                }
                aField.val('');
            });
            // //
            fields.push(aWrapper);
        });
        // append all the fields before the button's wrapper
        for (var i=0; i<fields.length; i++) {
            var field = fields[i];
            $(this).parent().prev().append(field);
        }
        // //
        initializeFileUpload(total); // rebind file upload events
    });

    $('#btn-add-contact-point').click(function () { // add founder button
        var total = $('.contact-point-number').length; // determine the order of the new group
        var fields = [];
        var chosenIds = []; // to trigger chosen plugin event later on
        var checkboxIds = []; // to check checkboxes if they are unchecked
        $('.contact-point-field-0').each(function () {
            var aWrapper = $(this).parent().parent().clone(); // clone the field with its wrapper
            // if it's a checkbox, it has two more wrappers
            if (typeof $(this).attr('type') !== 'undefined' && $(this).attr('type') === 'checkbox') {
                aWrapper = $(this).parent().parent().parent().parent().clone();
            }
            // //
            // loop through elements in the wrapper to rename id and name attributes
            aWrapper.find('*').each(function() {
                var aField = $(this);

                if (aField.hasClass('chosen-container')) {
                    aField.remove();
                }

                var fieldId = $(this).attr('id');
                if (typeof fieldId !== 'undefined') {
                    fieldId = fieldId.replace('_0', '_' + total);
                }
                $(aField).attr('id', fieldId);

                var fieldClass = $(this).attr('class');
                if (typeof fieldClass !== 'undefined') {
                    fieldClass = fieldClass.replace('-0', '-' + total);
                }
                $(aField).attr('class', fieldClass);

                var fieldName = $(this).attr('name');
                if (typeof fieldName !== 'undefined') {
                    fieldName = fieldName.replace('_0', '_' + total);
                }
                $(aField).attr('name', fieldName);

                var fieldFor = $(this).attr('for');
                if (typeof fieldFor !== 'undefined') {
                    fieldFor = fieldFor.replace('_0', '_' + total);
                }
                $(aField).attr('for', fieldFor);

                if (aField.hasClass('contact-point-number')) {
                    aField.text(total + 1);
                }

                if (!(aField.prop('tagName') !== 'undefined' && aField.prop('tagName').toLowerCase() === 'option')) {
                    aField.val('');
                }

                if (aField.hasClass('chosen')) {
                    aField.attr('style', '');
                    chosenIds.push(fieldId);
                }

                if (aField.attr('type') !== 'undefined' && aField.attr('type') === 'checkbox') {
                    if (aField.attr('checked') !== true) {
                        checkboxIds.push(fieldId);
                    }
                }
            });
            // //
            fields.push(aWrapper);
        });
        // append all the fields before the button's wrapper
        for (var i=0; i<fields.length; i++) {
            var field = fields[i];
            $(this).parent().prev().append(field);
        }
        // //
        for (var i = 0; i < chosenIds.length; i++) {
            $('#' + chosenIds[i]).parent().parent().show();
            $('#' + chosenIds[i]).chosen({disable_search_threshold: 10, search_contains: true});
            $('#' + chosenIds[i]).parent().parent().hide();
        }
        for (var i = 0; i < checkboxIds.length; i++) {
            $('#' + checkboxIds[i]).attr('checked', true);
        }
        if (chosenIds.length > 0 || checkboxIds.length > 0) {
            $('.contact-point-cb').unbind('change');
            $('.contact-point-cb').on('change', function () {
                $(this).parent().parent().parent().parent().next().toggle();
            });
        }
    });

    $('.use-internal-store-info').change(function () { // disabling some fields when the related cb is checked
        if ($(this).attr('checked') === 'checked') {
            $('.internal-store-info-field').attr('readonly', 'readonly');
        } else {
            $('.internal-store-info-field').removeAttr('readonly');
        }
    });

    if ($('#use-internal-store-info-value').length > 0) {
        $('.use-internal-store-info').click();
    }

    var total = $('.contact-point-number').length; // determine the order of the new group
    for (var i=0;i < total; i++) {
        $('.contact-point-field-' + i).addClass('contact-point-cb');
    }
    $('.contact-point-cb').on('change', function () {
        $(this).parent().parent().parent().parent().next().toggle();
    });
    $('.contact-point-cb:not(.off)').click();

    $('.founder-remove, .contact-point-remove').click(function () {
        var $grandParent = $(this).parent().parent().parent();
        $grandParent.next().next().next().next().next().next().next().next().remove();
        $grandParent.next().next().next().next().next().next().next().remove();
        $grandParent.next().next().next().next().next().next().remove();
        $grandParent.next().next().next().next().next().remove();
        $grandParent.next().next().next().next().remove();
        $grandParent.next().next().next().remove();
        $grandParent.next().next().remove();
        $grandParent.next().remove();
        $grandParent.remove();
    });

    $('.remove-image').click(function() {
        $(this).parent().parent().next().remove();
        $(this).parent().parent().remove();
    });

});