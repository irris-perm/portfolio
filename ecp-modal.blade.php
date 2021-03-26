<div class="modal fade" id="ecp_modal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0">
            <div class="modal-header rounded-0 p-0">
                <div id="ecp_sys" class="col modal-title text-center bg-warning p-3">
                    <h5>Проверка ЭЦП...</h5>
                </div>
            </div>
            <div class="modal-body">
                <div id="ecp_cert_b" class="form-group container d-none">
                    @isset($title)
                        <div class="alert alert-light text-dark p-2">
                            <h5>{{ $title }}</h5>
                        </div>
                    @endisset
                    @if (isset($text) and $text != 'false')
                        <textarea name="sign_text" class="form-control rounded-0"></textarea>
                        <br>
                    @endif
                    <select id="ecp_cert" class="form-control custom-select custom-select-lg rounded-0"
                            name="cert">
                        <option value="null">Выберите сертификат</option>
                    </select>
                    <input id="ecp_cert_name" type="text" name="cert_name" value="" hidden>
                </div>
                <div id="signed" class="form-group container d-none">
                    @isset($text)
                        <div class="alert alert-light text-dark p-2">
                            <h5>{{ $text }}</h5>
                        </div>
                    @endisset
                    @if (isset($file) and $file != 'false')
                        <div id="files"></div>
                    @endif
                    <hr>
                    <button id="form_btn" class="btn btn-block btn-lg btn-success rounded-0">
                        @isset($submit) {{ $submit }} @else Отправить @endisset
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-info btn-lg rounded-0" data-dismiss="modal">Закрыть
                </button>
            </div>
        </div>
    </div>
</div>

<script>

    $(document).ready(function () {
        var cert_show = 0;

        $('.ecp_button').click(function () {
            $(this).parents('form').prepend($('#ecp_modal'));
            $('#ecp_modal').modal('show');

            window.cryptoPro.getSystemInfo().then(function (systemInfo) {
                window.cryptoPro.isValidSystemSetup().then(function (isValidSystemSetup) {
                    systemInfo.isValidSystemSetup = isValidSystemSetup;
                    $('#ecp_sys').empty().removeClass('bg-warning bg-danger').addClass('bg-success');
                    for (var k in systemInfo) {
                        $('#ecp_sys').html($('#ecp_sys').html() + '<h5>' + k + ' : ' + systemInfo[k] + '</h5>');
                    }
                    userCert();
                }, function (error) {
                    error_sys(error.message);
                });
            }, function (error) {
                error_sys(error.message);
            });
        });

        function error_sys(message) {
            $('#ecp_sys').empty().removeClass('bg-warning bg-success').addClass('bg-danger').html('<h5>' + message + '</h5>');
            $('#ecp_cert_b').removeClass('d-block').addClass('d-none');
        }

        function userCert() {
            if (cert_show == 0) {
                window.cryptoPro.getUserCertificates()
                    .then(function (certificateList) {
                        certificateList.forEach(function (certificate) {
                            $('#ecp_cert').append('<option value="' + certificate.thumbprint + '">' + certificate.name + '. Действует до: ' + certificate.validTo.substr(0, 10) + '</option>');
                            cert_show = 1;
                        });
                    }, function (error) {
                        error_sys(error.message);
                    });
                $('#ecp_cert_b').removeClass('d-none').addClass('d-block');
            }
        }

        $('#ecp_cert').change(function () {
            if ($('#ecp_cert').val() == 'null') {
                $('#signed').removeClass('d-block').addClass('d-none');
            } else {
                $('#signed').removeClass('d-none').addClass('d-block');
                $('#ecp_cert_name').val($('#ecp_cert option:selected').text());
                $('#ecp_cert_val').val($(this).val());
            }

        });

        @if (isset($file) and $file != 'false')

        function app_f() {
            $('#files').prepend('<div class="file">' +
                '<input value="" hidden>' +
                '<label class="btn btn-lg btn-block btn-outline-info rounded-0">' +
                '<input type="file" class="ecp_docs" name="file[]" hidden>' +
                '<h5>Добавить и подписать файл</h5>' +
                '<button type="button" class="del_file btn btn-sm btn-secondary rounded-0 d-none">Удалить</button>' +
                '</label>' +
                '</div>');

        }

        app_f();

        $('#files').on('change', '.ecp_docs', function (e) {
            $('#form_btn').prop('disabled', true);

            var files = e.target.files, th = $(this).parent(), fr;

            for (var k = 0; k < files.length; k++) {
                th.children('h5').text(files[k].name);

                fr = new FileReader();
                fr.readAsArrayBuffer(files[k]);
                fr.addEventListener("load", function (e) {
                    var thumbprint = $('#ecp_cert').val(),
                        Base64 = window.btoa(new Uint8Array(e.target.result).reduce((data, byte) => data + String.fromCharCode(byte), ''));
                    window.cryptoPro.createSignature(thumbprint, Base64).then(function (signature) {
                        if (th.prev('input').val() == '') {
                            app_f();
                        }
                        th.children('.del_file').removeClass('d-none');
                        th.children('.del_file').addClass('d-inline-block');
                        th.prev('input').val(signature);
                        th.prev('input').prop('name', "sign_file[]");
                        th.children('h5').text(th.children('h5').text() + ' Подписан');
                        th.removeClass('btn-outline-info btn-danger');
                        th.addClass('btn-success');
                        $('#form_btn').prop('disabled', false);
                    }, function (error) {
                        th.removeClass('btn-success btn-outline-info');
                        th.addClass('btn-danger');
                        th.children('h5').text(th.children('h5').text() + ' ' + error.message);
                    });
                }, false);
            }
        });

        $('#files').on('click', '.del_file', function () {
            $(this).parent().remove();
        });

        @endif
    });

</script>
