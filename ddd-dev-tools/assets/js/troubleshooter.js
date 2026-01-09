// @version 2.1.0
jQuery(function ($) {
  const D = window.DDD_DT_TS || {}, defs = D.defaults || {}, engines = D.engines || {};
  const $res = $('#dt-search-results'), $cli = $('#dt-cli-preview'), $eng = $('#dt-engine');
  const esc = (s) => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  const mode = () => $('input[name="dt-mode"]:checked').val() || 'filename';
  const scope = () => $('#dt-scope').val() || 'plugin';
  const avail = (k) => k === 'php' || (engines[k] && engines[k].available);

  const uiScope = () => { $('#dt-plugin-row').toggle(scope() === 'plugin'); $('#dt-mu-row').toggle(scope() === 'mu_plugins'); };
  const uiEngine = () => {
    $eng.find('option').each(function () { const k = $(this).val(); $(this).prop('disabled', !avail(k)); });
    if (mode() === 'filename') { $eng.val('php').prop('disabled', true); $('#dt-engine-status').text('Filename mode uses PHP.'); }
    else { $eng.prop('disabled', false); $('#dt-engine-status').text(avail($eng.val()) ? '' : 'Selected engine unavailable; fallback to PHP.'); }
  };
  const rootPH = () => ({
    plugin: '<target-plugin>',
    plugins_active: 'wp-content/plugins/<active plugins>',
    plugins_inactive: 'wp-content/plugins/<inactive plugins>',
    plugins_all: 'wp-content/plugins',
    mu_plugins: 'wp-content/mu-plugins',
    themes_active: 'wp-content/themes/<active-theme>',
    wp_content: 'wp-content'
  }[scope()] || 'wp-content');
  const cli = () => {
    const m = mode(), term = $('#dt-search-term').val() || '<term>', r = rootPH();
    const i = $('#dt-ignore-case').is(':checked'), w = $('#dt-whole-word').is(':checked'), re = $('#dt-regex').is(':checked'), l = $('#dt-files-only').is(':checked');
    const exts = ($('#dt-extensions').val() || '').trim(), excl = ($('#dt-exclude-dirs').val() || '').trim(), e = $eng.val();
    let cmd = '';
    if (m === 'filename') cmd = `find ${r} -type f -iname "*${term}*"`;
    else if (e === 'grep') {
      const f = [i ? '-i' : '', w ? '-w' : '', re ? '-E' : '-F', l ? '-l' : '-n'].filter(Boolean).join(' ');
      cmd = `grep -r ${f} -e "${term}" ${r}` + (exts ? `  # --include='*.{${exts}}'` : '') + (excl ? `  # --exclude-dir={${excl}}` : '');
    } else if (e === 'rg') {
      const f = [i ? '-i' : '', w ? '-w' : '', l ? '--files-with-matches' : '', re ? '' : '--fixed-strings'].filter(Boolean).join(' ');
      cmd = `rg ${f} -e "${term}" ${r}` + (exts ? `  # -g '*.{${exts}}'` : '') + (excl ? `  # -g '!{${excl}}/**'` : '');
    } else cmd = 'PHP scan (no CLI command)';
    $cli.text(cmd);
  };
  const payloadBase = () => ({ scope: scope(), plugin: $('#dt-search-plugin').val(), mu_plugin: $('#dt-mu-plugin').val() });
  const render = (data) => {
    const meta = data.meta || {}, items = data.items || [];
    let h = `<div class="dt-meta">`;
    h += `<div><strong>Engine:</strong> ${esc(meta.engine_used || meta.engine || '')} (requested: ${esc(meta.engine_requested || '')})</div>`;
    h += `<div><strong>Scope:</strong> ${esc(meta.scope || '')} &nbsp; <strong>Mode:</strong> ${esc(meta.mode || '')}</div>`;
    h += `<div><strong>Matched files:</strong> ${esc(meta.matched_files)} &nbsp; <strong>Matches:</strong> ${esc(meta.matches)} &nbsp; <strong>Duration:</strong> ${esc(meta.duration_ms)}ms</div>`;
    if (meta.truncated) h += `<div class="dt-warn">Results truncated by limits.</div>`;
    if (meta.warnings && meta.warnings.length) h += `<div class="dt-warn">${esc(meta.warnings.join(' | '))}</div>`;
    h += `</div>`;
    if (!items.length) return $res.html(h + `<p>No matches found.</p>`);
    h += `<ul class="dt-results">`;
    items.forEach((it) => {
      const file = it.file || '', matches = it.matches || [], first = matches[0] ? matches[0].line : 1;
      h += `<li class="dt-file"><div class="dt-file-head"><span class="dt-path">${esc(file)}</span>`;
      h += ` <button class="button button-small dt-view-file" data-file="${esc(file)}" data-line="${esc(first)}">View</button></div>`;
      if (matches.length) {
        h += `<ul class="dt-matches">`;
        matches.forEach((m) => { h += `<li><a href="#" class="dt-view-line" data-file="${esc(file)}" data-line="${esc(m.line)}">L${esc(m.line)}</a>: <code>${m.text || ''}</code></li>`; });
        h += `</ul>`;
      }
      h += `</li>`;
    });
    $res.html(h + `</ul>`);
  };
  const modal = {
    open: (title, html) => { $('#dt-modal-title').text(title || ''); $('#dt-modal-body').html(html || ''); $('#dt-modal').show().attr('aria-hidden', 'false'); },
    close: () => { $('#dt-modal').hide().attr('aria-hidden', 'true'); $('#dt-modal-title').text(''); $('#dt-modal-body').html(''); },
  };
  const view = (file, line) => {
    const p = Object.assign(payloadBase(), { action: 'ddd_dt_ts_view', nonce: D.nonce_view, file, line, context: 20 });
    modal.open(file, 'Loading...');
    $.post(D.ajax_url, p, function (res) {
      if (!res || !res.success) return modal.open(file, `<span class="dt-error">${esc((res && res.data) || 'View failed.')}</span>`);
      const ex = res.data || {};
      const out = (ex.lines || []).map((r) => `<div class="dt-line${r.line === ex.focus_line ? ' is-focus' : ''}"><span class="dt-ln">${esc(r.line)}</span> ${r.text || ''}</div>`).join('');
      modal.open(ex.file || file, out || '(empty)');
    });
  };

  if (!D.enabled) {
    $('#dt-ts-disabled').show();
    return;
  }

  $('#dt-extensions').val(defs.extensions || '');
  $('#dt-exclude-dirs').val(defs.exclude_dirs || '');
  $('#dt-max-results').val(defs.max_results || 200);
  $('#dt-max-file-kb').val(defs.max_file_kb || 1024);
  $('#dt-max-ms').val(defs.max_ms || 8000);
  uiScope(); uiEngine(); cli();

  $('#dt-scope').on('change', () => { uiScope(); cli(); });
  $('input[name="dt-mode"]').on('change', () => { uiEngine(); cli(); });
  $('#dt-engine, #dt-search-term, #dt-ignore-case, #dt-whole-word, #dt-regex, #dt-files-only, #dt-extensions, #dt-exclude-dirs').on('change keyup', cli);
  $('#dt-modal-close').on('click', modal.close);
  $('#dt-modal').on('click', (e) => { if (e.target === e.currentTarget) modal.close(); });

  $('#dt-flush-cache, #dt-flush-rewrite').on('click', function () {
    const what = $(this).data('what');
    $('#dt-flush-result').text('Running...');
    $.post(D.ajax_url, { action: 'ddd_dt_ts_flush', nonce: D.nonce_flush, what }, (res) => $('#dt-flush-result').text(res && res.success ? 'Done.' : 'Failed.'));
  });

  $('#dt-search-btn').on('click', () => {
    const p = Object.assign(payloadBase(), {
      action: 'ddd_dt_ts_search', nonce: D.nonce_search, mode: mode(), term: $('#dt-search-term').val(), engine: $eng.val(),
      ignore_case: $('#dt-ignore-case').is(':checked') ? '1' : '0', whole_word: $('#dt-whole-word').is(':checked') ? '1' : '0',
      regex: $('#dt-regex').is(':checked') ? '1' : '0', files_only: $('#dt-files-only').is(':checked') ? '1' : '0',
      extensions: $('#dt-extensions').val(), exclude_dirs: $('#dt-exclude-dirs').val(), max_results: $('#dt-max-results').val(),
      max_file_kb: $('#dt-max-file-kb').val(), max_ms: $('#dt-max-ms').val(),
    });
    $res.html('<p>Searching...</p>');
    $.post(D.ajax_url, p, (res) => {
      if (!res || !res.success) return $res.html(`<p class="dt-error">${esc((res && res.data) || 'Search failed.')}</p>`);
      render(res.data);
    });
  });

  $('#dt-clear-btn').on('click', () => { $('#dt-search-term').val(''); $res.html(''); cli(); });
  $res.on('click', '.dt-view-line', function (e) { e.preventDefault(); view($(this).data('file'), $(this).data('line')); });
  $res.on('click', '.dt-view-file', function () { view($(this).data('file'), $(this).data('line')); });
});
