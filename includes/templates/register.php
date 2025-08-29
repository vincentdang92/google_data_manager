<?php $nonce = wp_create_nonce('gdm_register'); ?>
<div class="container my-4" id="gdm-register">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="mb-3">Đăng ký tài khoản</h4>
          <form id="gdm-otp-form">
            <div class="form-group">
              <label>Email</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="form-group">
              <label>Mật khẩu</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <button class="btn btn-info" type="button" id="btn-send-otp">Gửi OTP</button>

            <div class="form-group mt-3">
              <label>Nhập OTP 6 số</label>
              <input type="text" class="form-control" name="otp" maxlength="6" pattern="\d{6}">
            </div>
            <button class="btn btn-primary btn-block" type="submit">Xác thực & Tạo tài khoản</button>
          </form>
          <p class="mt-3 mb-0">Đã có tài khoản? <a href="<?php echo esc_url(home_url('/gdm-login')); ?>">Đăng nhập</a></p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
jQuery(function($){
  const ajaxurl = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
  const nonce = <?php echo json_encode($nonce); ?>;

  $('#btn-send-otp').on('click', function(){
    const email = $('#gdm-otp-form [name="email"]').val();
    if(!email) return alert('Nhập email');
    $.post(ajaxurl, { action:'gdm_request_otp', nonce, email }, function(res){
      if(res.success) alert(res.data.message);
      else alert(res.data.message || 'Lỗi gửi OTP');
    });
  });

  $('#gdm-otp-form').on('submit', function(e){
    e.preventDefault();
    const email = this.email.value, password=this.password.value, otp=this.otp.value;
    $.post(ajaxurl, { action:'gdm_verify_otp', nonce, email, password, otp }, function(res){
      if(res.success) {
        alert(res.data.message);
        location.href = <?php echo json_encode(home_url('/gdm-login')); ?>;
      } else alert(res.data.message || 'Lỗi xác thực');
    });
  });
});
</script>
