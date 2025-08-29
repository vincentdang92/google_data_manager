<?php
use GDM_Auth as Auth; // alias nếu cần
$google_url = GDM_Auth::google_login_url();
?>
<div class="container my-4" id="gdm-login">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="mb-3">Đăng nhập</h4>

          <button onclick="location.href='<?php echo esc_url($google_url); ?>'"
                  class="btn btn-danger btn-block mb-3">Đăng nhập với Google</button>

          <hr>
          <form id="gdm-login-form">
            <div class="form-group">
              <label>Tài khoản (email/username)</label>
              <input type="text" class="form-control" name="username" required>
            </div>
            <div class="form-group">
              <label>Mật khẩu</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <input type="hidden" name="recaptcha_token" id="gdm_recaptcha_token">
            <button class="btn btn-primary btn-block" type="submit">Đăng nhập</button>
          </form>

          <p class="mt-3 mb-0">Chưa có tài khoản? <a href="<?php echo esc_url(home_url('/gdm-register')); ?>">Đăng ký</a></p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // reCAPTCHA v3: tạo token action=login
  if (typeof grecaptcha !== 'undefined' && grecaptcha.execute) {
    grecaptcha.ready(function() {
      const site = <?php echo json_encode((get_option('gdm_settings')['recaptcha_v3_site'] ?? '')); ?>;
      if (site) {
        grecaptcha.execute(site, {action: 'login'}).then(function(token) {
          document.getElementById('gdm_recaptcha_token').value = token;
        });
      }
    });
  }
  // Submit AJAX -> REST
  jQuery('#gdm-login-form').on('submit', function(e){
    e.preventDefault();
    const payload = {
      username: this.username.value,
      password: this.password.value,
      recaptcha_token: this.recaptcha_token.value,
      recaptcha_action: 'login'
    };
    fetch(GDM_VARS.rest + 'login', {
      method: 'POST',
      headers: {'Content-Type':'application/json','X-WP-Nonce': GDM_VARS.nonce},
      body: JSON.stringify(payload)
    }).then(r=>r.json()).then(res=>{
      if(res.redirect){ location.href = res.redirect; }
      else alert(res.message || 'OK');
    }).catch(()=>alert('Có lỗi xảy ra'));
  });
});
</script>
