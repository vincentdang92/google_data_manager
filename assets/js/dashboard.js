(function(){
  const rest = (path, opts={}) =>
    fetch(GDM_VARS.rest + path, {
      ...opts,
      headers: { 'X-WP-Nonce': GDM_VARS.nonce, ...(opts.headers||{}) }
    }).then(r=>r.json());

  // Dark/Light toggle
  document.addEventListener('DOMContentLoaded', function(){
    const root = document.documentElement;
    const wrap = document.getElementById('gdm-app');
    const btn = document.getElementById('gdm-theme-toggle');
    if (btn && wrap) {
      const saved = localStorage.getItem('gdm-theme') || 'light';
      if (saved === 'dark') wrap.classList.add('gdm-dark');
      btn.addEventListener('click', () => {
        wrap.classList.toggle('gdm-dark');
        localStorage.setItem('gdm-theme', wrap.classList.contains('gdm-dark') ? 'dark' : 'light');
      });
    }
  });

  // Date range picker
  let minDate = '', maxDate = '';
  document.addEventListener('DOMContentLoaded', function(){
    const input = document.getElementById('gdm-date-range');
    if (input && window.Litepicker) {
      const picker = new Litepicker({
        element: input,
        singleMode: false,
        format: 'YYYY-MM-DD',
        numberOfMonths: 2,
        numberOfColumns: 2,
        dropdowns: { years: true, months: true }
      });
      picker.on('selected', (date1, date2) => {
        minDate = date1 ? date1.format('YYYY-MM-DD') : '';
        maxDate = date2 ? date2.format('YYYY-MM-DD') : '';
        document.querySelector('[name="min_date"]').value = minDate;
        document.querySelector('[name="max_date"]').value = maxDate;
      });
    }
  });

  // Stats
  function loadStats(params={}) {
    rest('stats').then(s=>{
      const nf = new Intl.NumberFormat();
      const cf = new Intl.NumberFormat(undefined, {style:'currency', currency:'USD'});
      document.getElementById('stat-total').textContent = nf.format(s.total_rows||0);
      document.getElementById('stat-sum').textContent = nf.format(s.total_amount||0);
      document.getElementById('stat-avg').textContent = nf.format(s.avg_amount||0);
    });
  }

  // DataTable
  let dt;
  function buildAjaxData(d){
    // append extra filters to datatables params
    const form = document.getElementById('gdm-filters');
    const fd = new FormData(form);
    d.min_date = fd.get('min_date') || '';
    d.max_date = fd.get('max_date') || '';
    d.min_amount = fd.get('min_amount') || '';
    d.max_amount = fd.get('max_amount') || '';
  }

  document.addEventListener('DOMContentLoaded', function(){
    const tableEl = document.getElementById('gdm-table');
    if (!tableEl) return;

    dt = jQuery('#gdm-table').DataTable({
      processing: true,
      serverSide: true,
      searching: true,
      responsive: { details: { type: 'column' } },
      ajax: {
        url: GDM_VARS.rest + 'data',
        type: 'GET',
        beforeSend: function (xhr) { xhr.setRequestHeader('X-WP-Nonce', GDM_VARS.nonce); },
        data: buildAjaxData
      },
      lengthMenu: [10, 25, 50, 100],
      order: [],
      dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
           "<'row'<'col-sm-12'tr>>" +
           "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
      buttons: [
        { extend: 'csvHtml5', text: 'CSV ⬇️', className: 'btn btn-outline-primary btn-sm' },
        { extend: 'excelHtml5', text: 'Excel ⬇️', className: 'btn btn-outline-success btn-sm' },
        { extend: 'print', text: 'In', className: 'btn btn-outline-secondary btn-sm' }
      ],
      columnDefs: [
        { targets: [2], className: 'text-nowrap' },
        { targets: [3], className: 'text-right' }
      ]
    });

    // Filters apply / reset
    const form = document.getElementById('gdm-filters');
    form.addEventListener('submit', function(e){ e.preventDefault(); dt.ajax.reload(); });
    document.getElementById('gdm-reset').addEventListener('click', function(){
      form.reset();
      document.querySelector('[name="min_date"]').value = '';
      document.querySelector('[name="max_date"]').value = '';
      document.getElementById('gdm-date-range').value = '';
      dt.ajax.reload();
    });

    // Sync button
    const btnSync = document.getElementById('gdm-sync');
    if (btnSync) {
      btnSync.addEventListener('click', function(){
        rest('sync', { method: 'POST' }).then(res=>{
          alert(res.message || 'Đã đồng bộ');
          dt.ajax.reload();
          loadStats();
        }).catch(()=>alert('Lỗi sync'));
      });
    }

    // Initial stats
    loadStats();
  });
})();
