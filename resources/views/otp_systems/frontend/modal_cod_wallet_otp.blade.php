<div class="modal fade" id="cod_wallet_otp-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="purchaseOtpForm">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Verify Checkout OTP') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label>{{ translate('Verification Code') }}</label>
                        <input type="text" name="otp_code" class="form-control" inputmode="numeric" autocomplete="one-time-code" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Verify') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
