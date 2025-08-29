<?php
$current_user = wp_get_current_user();
$display_name = esc_html($current_user->display_name ?: $current_user->user_email);
?>
<div id="gdm-app" class="gdm-wrap">

  <!-- Sidebar -->
  <aside class="gdm-sidebar">
    <div class="gdm-brand">
      <span class="gdm-logo">G</span>
      <strong>Google Data Manager</strong>
    </div>
    <nav class="gdm-nav">
      <a class="active" href="#table"><i class="gdm-ico">📊</i> Bảng dữ liệu</a>
      <a href="#stats"><i class="gdm-ico">📈</i> Thống kê</a>
      <a href="<?php echo esc_url(home_url('/gdm-login')); ?>"><i class="gdm-ico">🔐</i> Đăng nhập</a>
      <a href="<?php echo esc_url(wp_logout_url(home_url('/gdm-login'))); ?>"><i class="gdm-ico">🚪</i> Đăng xuất</a>
    </nav>
    <div class="gdm-footer">
      <button id="gdm-theme-toggle" class="btn btn-sm btn-outline-secondary">🌗 Dark/Light</button>
    </div>
  </aside>

  <!-- Main -->
  <main class="gdm-main container-fluid">
    <!-- Topbar -->
    <div class="gdm-topbar d-flex align-items-center justify-content-between">
      <div>
        <h5 class="mb-0">Xin chào, <?php echo $display_name; ?></h5>
        <small class="text-muted">Quản lý & tra cứu dữ liệu đã đồng bộ</small>
      </div>
      <div class="d-flex align-items-center">
        <button id="gdm-sync" class="btn btn-success mr-2">🔄 Đồng bộ dữ liệu của tôi</button>
        <a class="btn btn-outline-secondary" href="<?php echo esc_url(home_url('/')); ?>">🏠 Về trang chủ</a>
      </div>
    </div>

    <!-- Cards -->
    <section id="stats" class="row gdm-cards">
      <div class="col-md-4 mb-3">
        <div class="card gdm-card shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <div class="text-muted">Tổng bản ghi</div>
                <div class="h4 mb-0" id="stat-total">—</div>
              </div>
              <div class="gdm-kpi-ico">🗂️</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card gdm-card shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <div class="text-muted">Tổng số tiền</div>
                <div class="h4 mb-0" id="stat-sum">—</div>
              </div>
              <div class="gdm-kpi-ico">💰</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card gdm-card shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <div class="text-muted">Trung bình</div>
                <div class="h4 mb-0" id="stat-avg">—</div>
              </div>
              <div class="gdm-kpi-ico">📐</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Filters -->
    <section class="card shadow-sm mb-3">
      <div class="card-body">
        <form id="gdm-filters" class="form-inline">
          <div class="form-group mr-2 mb-2">
            <label class="mr-2">Ngày</label>
            <input type="text" id="gdm-date-range" class="form-control" placeholder="Chọn khoảng ngày">
            <input type="hidden" name="min_date">
            <input type="hidden" name="max_date">
          </div>
          <div class="form-group mr-2 mb-2">
            <label class="mr-2">Tối thiểu</label>
            <input type="number" step="0.01" class="form-control" name="min_amount" placeholder="0">
          </div>
          <div class="form-group mr-2 mb-2">
            <label class="mr-2">Tối đa</label>
            <input type="number" step="0.01" class="form-control" name="max_amount" placeholder="">
          </div>
          <button class="btn btn-primary mb-2 mr-2" type="submit">Áp dụng</button>
          <button class="btn btn-outline-secondary mb-2" type="button" id="gdm-reset">Reset</button>
        </form>
      </div>
    </section>

    <!-- Table -->
    <section id="table" class="card shadow-sm">
      <div class="card-body">
        <table id="gdm-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tên</th>
              <th>Ngày</th>
              <th>Số tiền</th>
              <th>Email</th>
            </tr>
          </thead>
        </table>
      </div>
    </section>
  </main>
</div>
