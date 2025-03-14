jQuery(document).ready(function ($) {
    var file_frame;
    $('#upload_gallery_button').on('click', function (event) {
        event.preventDefault();

        if (file_frame) {
            file_frame.open();
            return;
        }

        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select or Upload Images',
            button: {
                text: 'Use these images'
            },
            multiple: true
        });

        file_frame.on('select', function () {
            var attachments = file_frame.state().get('selection').map(function (attachment) {
                attachment = attachment.toJSON();
                return attachment.id;
            });

            var ids = attachments.join(',');
            $('#immobile_gallery').val(ids);

            var gallery_preview = $('#gallery_preview');
            gallery_preview.empty();
            attachments.forEach(function (id) {
                wp.media.attachment(id).fetch().then(function (attachment) {
                    gallery_preview.append('<img src="' + attachment.sizes.thumbnail.url + '" style="width: 60px;" />');
                });
            });
        });

        file_frame.open();
    });

    const forms = {
        "form#add-immobile": {
            action: "create_immobile",
        },
        "form#edit-immobile": {
            action: "update_immobile",
        },
        "form#add-broker": {
            action: "create_broker",
        },
        "form#edit-broker": {
            action: "update_broker",
        },
        "form#add-lead": {
            action: "create_lead",
        },
        "form#edit-lead": {
            action: "update_lead",
        },
        "form#add-location": {
            action: "create_location",
        },
        "form#delete-location": {
            action: "delete_location"
        },
        "form#delete-broker": {
            action: "delete_broker"
        },
        "form#delete_post": {
            action: "delete_post",
            redirect: 'back'
        },
    }

    Object.entries(forms).forEach(([selector, item]) => {
        verifyOnChangeInputs(selector);

        if (item.action.includes('delete')) {
            window.onbeforeunload = function (e) {
                jQuery(window).unbind();
                return undefined;
            };
        }

        let sendRequest = (data, redirect) => {
            $.ajax({
                url: site.ajax_url,
                method: 'POST',
                data: data,
                success: function (response, textStatus, jqXHR) {
                    if (item.action.includes('delete')) {
                        Swal.fire({
                            title: `Sucesso!`,
                            text: response.data,
                            icon: "success",
                            confirmButtonColor: "#16a34a",
                            confirmButtonText: "OK",
                        }).then((result) => {
                            if (redirect) {
                                window.history.back();
                            } else {
                                window.location.reload();
                            }
                        });
                    } else if (item.action.includes('update')) {
                        Swal.fire({
                            title: `Sucesso!`,
                            text: response.data,
                            icon: "success",
                            confirmButtonColor: "#16a34a",
                            confirmButtonText: "OK",
                        }).then(() => {
                            window.history.back();
                        });
                    } else {
                        Swal.fire({
                            title: 'Sucesso!',
                            text: `Deseja Cadastrar outro ${response.data}.`,
                            icon: "success",
                            showCancelButton: true,
                            confirmButtonColor: "#16a34a",
                            cancelButtonColor: "#dc2626",
                            confirmButtonText: "Cadastrar",
                            cancelButtonText: "Voltar"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            } else {
                                window.history.back();
                            }
                        });
                    }
                },
                error: function (response, a, ad) {
                    Swal.fire({
                        title: "Erro!",
                        text: response.responseJSON.data,
                        icon: "error"
                    });
                }
            });
        }

        $(selector).on('submit', function (event) {
            event.preventDefault();
            const formData = $(this).serializeArray();
            let data = {};
            $(formData).each(function (index, obj) {
                if (data[obj.name]) {
                    if (!Array.isArray(data[obj.name])) {
                        data[obj.name] = [data[obj.name]];
                    }
                    data[obj.name].push(obj.value);
                } else {
                    data[obj.name] = obj.value;
                }
            });

            data.action = item.action;
            data.nonce = site.nonce;

            if (item.action.includes('delete')) {
                Swal.fire({
                    title: `Tem certeza que deseja deletar?.`,
                    text: `Essa ação é irreversível.`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#dc2626",
                    cancelButtonColor: "#16a34a",
                    confirmButtonText: "Deletar",
                    cancelButtonText: "Cancelar"
                }).then((result) => {
                    if (result.isConfirmed) {
                        sendRequest(data, item.redirect || '')
                    }
                });
            } else {
                sendRequest(data, item.redirect || '')
            }
        });
    });

    function verifyOnChangeInputs(form) {
        $(form).find('input').each((i, element) => {
            $(element).on("focusout", function () {
                const isValid = element.checkValidity() || !$(element).is('[required]');
                if (isValid) {
                    $(element).removeClass("invalid");
                } else {
                    $(element).addClass("invalid");
                }
            });
        });
    }

    $("[name*='amount']").on("focusout", function () {
        let value = $(this).val().replaceAll('.', '');
        value = parseInt(value);

        if (isNaN(value)) {
            $(this).val('0');
            return;
        }

        value = value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")
        $(this).val(value);
    });

    $('.copy-link').on('click', function () {
        const link = $(this).attr('data-href');

        navigator.clipboard.writeText(link).then(function () {
            Swal.fire({
                title: `Sucesso!`,
                text: "Link Copiado.",
                icon: "success",
                showConfirmButton: false,
                timer: 1500,
                customClass: {
                    confirmButton: "btn btn-info",
                },
                buttonsStyling: false
            });
        });
    });

    $("#gerar-link").on('click', function (event) {
        event.preventDefault();
        Swal.fire({
            title: "Digite o nome do Cliente:",
            input: "text",
            showCancelButton: true,
            confirmButtonText: "Gerar Link",
            customClass: {
                confirmButton: "btn btn-success",
                cancelButton: "btn btn-gray"
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                let postIds = [];
                jQuery('[post-id]').each((i, e) => {
                    postIds.push(e.getAttribute('post-id'));
                });
                postIds.pop();
                postIds = postIds.join(',');

                $.ajax({
                    url: site.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'create_link_listaimoveis',
                        name: result.value,
                        immobile_ids: postIds,
                        nonce: site.nonce
                    },
                    success: function (response) {
                        const link = response.data.link;
                        Swal.fire({
                            title: `Sucesso!`,
                            text: 'Link Gerado.',
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500,
                            customClass: {
                                confirmButton: "btn btn-success",
                            },
                            buttonsStyling: false
                        });
                        $('.copy-link').attr('data-href', link).show();
                        $('.copy-link span').text(link);

                    },
                    error: function (response) {
                        Swal.fire({
                            title: "Erro!",
                            text: response.responseText,
                            icon: "error",
                            customClass: {
                                confirmButton: "btn btn-info",
                            },
                            buttonsStyling: false
                        });
                    }
                });
            }
        });
    });

    $(".btn-close").on('click', function (event) {
        event.preventDefault();
        $(this).parent().parent().remove();
    });

    $(".select2").select2();
});