<script type="text/javascript">

    function submitShippingInfoForm(el) {
        var email = $("input[name='email']").val();
        var phone = $("input[name='country_code']").val()+$("input[name='phone']").val();
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('guest_customer_info_check')}}",
            type: 'POST',
            data: {
                email : email,
                phone : phone
            },
            success: function (response) {
                if(response ==  1){
                    $('#login_modal').modal();
                    AIZ.plugins.notify('warning', '{{ translate('You already have an account with this information. Please Login first.') }}');
                }
                else{
                    $('#shipping_info_form').submit();
                }
            }
        });
    }

    function add_new_address(){
        $('#new-address-modal').modal('show');
    }

     function add_new_billing_address(){
        $('#new-billing-address-modal').modal('show');
    }

    function edit_address(address) {
        var url = '{{ route("addresses.edit", ":id") }}';
        url = url.replace(':id', address);

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url,
            type: 'GET',
            success: function (response) {
                $('#edit_modal_body').html(response.html);
                $('#edit-address-modal').modal('show');
                AIZ.plugins.bootstrapSelect('refresh');

                @if (get_setting('google_map') == 1 && empty($checkoutMapDisabled))
                    var lat     = -33.8688;
                    var long    = 151.2195;

                    if(response.data.address_data.latitude && response.data.address_data.longitude) {
                        lat     = parseFloat(response.data.address_data.latitude);
                        long    = parseFloat(response.data.address_data.longitude);
                    }

                    initialize(lat, long, 'edit_');
                @endif
                @if(get_active_countries()->count() == 1)
                    if (response.data.address_data.country_id != {{ get_active_countries()->first()->id }}) {
                        get_states({{ get_active_countries()->first()->id }}, '#edit_modal_body form');
                    }
                @endif
            }
        });
    }

    function edit_billing_address(address) {
        var url = '{{ route("billing_addresses.edit", ":id") }}';
        url = url.replace(':id', address);

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url,
            type: 'GET',
            success: function (response) {
                $('#edit_modal_body').html(response.html);
                $('#edit-address-modal').modal('show');
                AIZ.plugins.bootstrapSelect('refresh');

                @if (get_setting('google_map') == 1 && empty($checkoutMapDisabled))
                    var lat     = -33.8688;
                    var long    = 151.2195;

                    if(response.data.address_data.latitude && response.data.address_data.longitude) {
                        lat     = parseFloat(response.data.address_data.latitude);
                        long    = parseFloat(response.data.address_data.longitude);
                    }

                    initialize(lat, long, 'edit_');
                @endif
                @if(get_active_countries()->count() == 1)
                    if (response.data.address_data.country_id != {{ get_active_countries()->first()->id }}) {
                        get_states({{ get_active_countries()->first()->id }}, '#edit_modal_body form');
                    }
                @endif
            }
        });
    }

    $(document).on('change', '[name=country_id]', function() {
        var country_id = $(this).val();
        @if(get_setting('has_state') == 1)
            get_states(country_id, this);
        @else
            get_city_by_country(country_id, this);
        @endif
    });

    $(document).on('change', '[name=state_id]', function() {
        var state_id = $(this).val();
        get_city(state_id, this);
    });

    $(document).on('change', '[name=city_id]', function() {
        var city_id = $(this).val();
        get_area(city_id, this);
    });


    $(document).on('change', '[name=billing_country_id]', function() {
        var country_id = $(this).val();
        @if(get_setting('has_state') == 1)
            get_billing_states(country_id, this);
        @else
            get_billing_city_by_country(country_id, this);
        @endif
    });

    $(document).on('change', '[name=billing_state_id]', function() {
        var state_id = $(this).val();
        get_billing_city(state_id, this);
    });

    $(document).on('change', '[name=billing_city_id]', function() {
        var city_id = $(this).val();
        get_billing_area(city_id, this);
    });

    function fieldScope(context) {
        if (!context) {
            return $(document);
        }

        var $context = $(context);
        var $scope = $context.closest('form, .modal, .tab-pane, #shipping_info');
        return $scope.length ? $scope : $context;
    }

    function scopedField($scope, name) {
        var $field = $scope.find('[name="' + name + '"]');
        return $field.length ? $field : $('[name="' + name + '"]');
    }

    function setManualCityMode(selectName, inputName, enabled, context) {
        var $scope = fieldScope(context);
        var $select = scopedField($scope, selectName);
        var $input = scopedField($scope, inputName);

        if (!$select.length || !$input.length) {
            return;
        }

        var $selectWidget = $select.closest('.bootstrap-select');
        if (!$selectWidget.length) {
            $selectWidget = $select;
        }

        var $toggle = $scope.find('[data-select-name="' + selectName + '"][data-input-name="' + inputName + '"]');

        if (enabled) {
            $select.val('').prop('required', false).prop('disabled', true);
            $selectWidget.addClass('d-none');
            $input.prop('disabled', false).prop('required', true).removeClass('d-none');
            $toggle.text('{{ translate('Choose from city list') }}');

            if (selectName == 'city_id') {
                scopedField($scope, 'area_id').removeAttr('required').html('');
                $scope.find('.area-field').addClass('d-none');
            } else if (selectName == 'billing_city_id') {
                scopedField($scope, 'billing_area_id').removeAttr('required').html('');
                $scope.find('.billing-area-field').addClass('d-none');
            }
        } else {
            $input.val('').prop('disabled', true).prop('required', false).addClass('d-none');
            $select.prop('disabled', false).prop('required', true);
            $selectWidget.removeClass('d-none');
            $toggle.text('{{ translate('City not listed? Enter manually') }}');
        }

        AIZ.plugins.bootstrapSelect('refresh');
        if (typeof stepCompletionShippingInfo === 'function') {
            stepCompletionShippingInfo();
        }
    }

    $(document).on('click', '.js-toggle-manual-city', function() {
        var selectName = $(this).data('select-name');
        var inputName = $(this).data('input-name');
        var $scope = fieldScope(this);
        var isManual = !scopedField($scope, inputName).prop('disabled');
        setManualCityMode(selectName, inputName, !isManual, this);
    });

    function get_states(country_id, context) {
        var $scope = fieldScope(context);
        scopedField($scope, 'state').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-state')}}",
            type: 'POST',
            data: {
                country_id  : country_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                if(obj != '') {
                    scopedField($scope, 'state_id').html(obj);
                    AIZ.plugins.bootstrapSelect('refresh');
                }
            }
        });
    }

    function get_billing_states(country_id, context) {
        var $scope = fieldScope(context);
        scopedField($scope, 'billing_state').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-state')}}",
            type: 'POST',
            data: {
                country_id  : country_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                if(obj != '') {
                    scopedField($scope, 'billing_state_id').html(obj);
                    AIZ.plugins.bootstrapSelect('refresh');
                }
            }
        });
    }



    function get_city(state_id, context) {
        var $scope = fieldScope(context);
        scopedField($scope, 'city').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-city')}}",
            type: 'POST',
            data: {
                state_id: state_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                if(obj != ''&& $('<select></select>').html(obj).find('option').length > 1) {
                    setManualCityMode('city_id', 'city_name', false, $scope);
                    scopedField($scope, 'city_id').attr('disabled', false);
                    scopedField($scope, 'city_id').html(obj);
                    AIZ.plugins.bootstrapSelect('refresh');
                }else{
                    scopedField($scope, 'city_id').html('<option value="">{{ translate('No cities are available under this state.') }}</option>');
                    scopedField($scope, 'city_id').attr('disabled', true);
                    AIZ.plugins.bootstrapSelect('refresh');
                    setManualCityMode('city_id', 'city_name', true, $scope);
                }
            }
        });
    }

    function get_billing_city(state_id, context) {
        var $scope = fieldScope(context);
        scopedField($scope, 'billing_city').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-city')}}",
            type: 'POST',
            data: {
                state_id: state_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                if(obj != ''&& $('<select></select>').html(obj).find('option').length > 1) {
                    setManualCityMode('billing_city_id', 'billing_city_name', false, $scope);
                    scopedField($scope, 'billing_city_id').attr('disabled', false);
                    scopedField($scope, 'billing_city_id').html(obj);
                    AIZ.plugins.bootstrapSelect('refresh');
                }else{
                    scopedField($scope, 'billing_city_id').html('<option value="">{{ translate('No cities are available under this state.') }}</option>');
                    scopedField($scope, 'billing_city_id').attr('disabled', true);
                    AIZ.plugins.bootstrapSelect('refresh');
                    setManualCityMode('billing_city_id', 'billing_city_name', true, $scope);
                }
            }
        });
    }

    

    function get_area(city_id, context) {
        var $scope = fieldScope(context);
        scopedField($scope, 'area').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-area')}}",
            type: 'POST',
            data: {
                city_id: city_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                scopedField($scope, 'area_id').html(obj);
                AIZ.plugins.bootstrapSelect('refresh');
                if (obj.includes('<option') && !obj.includes('disabled selected')) {
                    scopedField($scope, 'area_id').attr('required', true);
                    $scope.find('.area-field').removeClass('d-none');
                } else {
                    scopedField($scope, 'area_id').removeAttr('required');
                    $scope.find('.area-field').addClass('d-none');
                }
            }
        });
    }


    function get_city_by_country(country_id, context){
        var $scope = fieldScope(context);
        scopedField($scope, 'city').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-city-by-country')}}",
            type: 'POST',
            data: {
                country_id: country_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                if(obj != '' && $('<select></select>').html(obj).find('option').length > 1) {
                    setManualCityMode('city_id', 'city_name', false, $scope);
                    scopedField($scope, 'city_id').attr('disabled', false);
                    scopedField($scope, 'city_id').html(obj);
                    AIZ.plugins.bootstrapSelect('refresh');
                }else{
                    scopedField($scope, 'city_id').html('<option value="">{{ translate('No cities are available under this country.') }}</option>');
                    scopedField($scope, 'city_id').attr('disabled', true);
                    AIZ.plugins.bootstrapSelect('refresh');
                    setManualCityMode('city_id', 'city_name', true, $scope);
                }
            }
        });
    }


     function get_billing_area(city_id, context) {
        var $scope = fieldScope(context);
        scopedField($scope, 'billing_area').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-area')}}",
            type: 'POST',
            data: {
                city_id: city_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                scopedField($scope, 'billing_area_id').html(obj);
                AIZ.plugins.bootstrapSelect('refresh');
                if (obj.includes('<option') && !obj.includes('disabled selected')) {
                    scopedField($scope, 'billing_area_id').attr('required', true);
                    $scope.find('.billing-area-field').removeClass('d-none');
                } else {
                    scopedField($scope, 'billing_area_id').removeAttr('required');
                    $scope.find('.billing-area-field').addClass('d-none');
                }
            }
        });
    }


    function get_billing_city_by_country(country_id, context){
        var $scope = fieldScope(context);
        scopedField($scope, 'billing_city').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-city-by-country')}}",
            type: 'POST',
            data: {
                country_id: country_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                if(obj != '' && $('<select></select>').html(obj).find('option').length > 1) {
                    setManualCityMode('billing_city_id', 'billing_city_name', false, $scope);
                    scopedField($scope, 'billing_city_id').attr('disabled', false);
                    scopedField($scope, 'billing_city_id').html(obj);
                    AIZ.plugins.bootstrapSelect('refresh');
                }else{
                    scopedField($scope, 'billing_city_id').html('<option value="">{{ translate('No cities are available under this country.') }}</option>');
                    scopedField($scope, 'billing_city_id').attr('disabled', true);
                    AIZ.plugins.bootstrapSelect('refresh');
                    setManualCityMode('billing_city_id', 'billing_city_name', true, $scope);
                }
            }
        });
    }

   
    $(document).on('change', '#sameAsShipping', function () {

        const billingTab  = $('#profile-tab');
        const billingPane = $('#billing-address');

        if (!billingTab.length || !billingPane.length) {
            return;
        }

        if (this.checked) {
            billingTab
                .addClass('disabled')
                .removeAttr('data-toggle')
                .attr('aria-disabled', 'true')
                .css('pointer-events', 'none');

            if (billingTab.hasClass('active')) {
                $('.nav-link:not(#profile-tab)').first().tab('show');
            }
            billingPane.find('input, textarea, select').each(function () {
                $(this).val('');
            });
            billingPane.find('[required]').each(function () {
                $(this).data('was-required', true).removeAttr('required');
            });

            billingPane.removeClass('show active').hide();

        } else {
            billingTab
                .removeClass('disabled')
                .attr('data-toggle', 'tab')
                .attr('aria-disabled', 'false')
                .css('pointer-events', '');
            billingPane.find('[data-was-required]').each(function () {
                $(this).attr('required', true).removeData('was-required');
            });
            billingPane.show();
        }
    });


</script>
