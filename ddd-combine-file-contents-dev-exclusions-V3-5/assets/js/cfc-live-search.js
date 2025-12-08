/**
 * File: assets/js/cfc-live-search.js
 * Description: File-tree live search + enhanced preview (line numbers, match count, current file).
 */
(function($){
    'use strict';

    let lastQuery = '';

    $(document).ready(function(){

        // 1) File-tree search bar (left column)
        const $treeContainer = $('#cfc-directory-tree-container');
        if ($treeContainer.length) {
            const $fileSearchBar = $(
                '<div style="position:sticky; top:0; background:#fff; z-index:10; margin-bottom:6px;">' +
                    '<input type="text" id="cfc-file-search" placeholder="Search files..." style="width:100%;" />' +
                '</div>'
            );
            $treeContainer.prepend($fileSearchBar);

            let typingTimer;
            const debounceDelay = 300;
            $('#cfc-file-search').on('input', function(){
                const query = $(this).val().trim();
                if (typingTimer) {
                    clearTimeout(typingTimer);
                }
                typingTimer = setTimeout(function(){
                    handleFileSearch(query);
                }, debounceDelay);
            });
        }

        // 2) Combined preview: line numbers + search + current-file indicator
        const $codeTextarea = $('#cfc-preview-textarea');
        if ($codeTextarea.length && $codeTextarea.val().trim() !== '') {

            $codeTextarea.hide();

            // Scrollable highlight container
            const $previewContainer = $('<div id="cfc-preview-highlight" tabindex="0"></div>');

            // Bottom search bar for code
            const $codeSearchBar = $(
                '<div style="margin:10px 0;">' +
                    '<input type="text" id="cfc-code-search" placeholder="Search in code..." style="width:calc(100% - 160px);" />' +
                    ' <span id="cfc-code-count" style="margin-left:8px;">0/0</span>' +
                '</div>'
            );

            // Current file indicator (little pill above preview)
            const $fileIndicator = $('<div id="cfc-current-file-indicator"></div>');

            // Insert elements after textarea
            $codeTextarea.after($codeSearchBar).after($previewContainer);
            $previewContainer.before($fileIndicator);

            // Build highlighted sections (per file) with line numbers
            buildHighlightedPreview($codeTextarea.val(), $previewContainer[0]);

            // Initial indicator
            updateCurrentFileIndicator();

            // Update indicator while scrolling
            $previewContainer.on('scroll', function(){
                updateCurrentFileIndicator();
            });

            // Code search input
            $('#cfc-code-search').on('input', function(){
                handleCodeSearch($(this).val());
            });

            // Enter / Shift+Enter to jump between matches
            $('#cfc-code-search').on('keydown', function(e){
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const term   = $(this).val().trim();
                    const $marks = $('#cfc-preview-highlight mark.cfc-term-highlight');
                    if (!term || !$marks.length) {
                        return;
                    }
                    let idx = parseInt($(this).data('currentIndex'), 10) || 0;
                    idx = e.shiftKey
                        ? (idx - 1 + $marks.length) % $marks.length
                        : (idx + 1) % $marks.length;
                    $(this).data('currentIndex', idx);
                    updateCount(idx + 1, $marks.length);

                    $('#cfc-preview-highlight mark.cfc-term-highlight').removeClass('current-match');
                    const el = $marks.get(idx);
                    $(el).addClass('current-match');
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // After jumping, refresh current-file indicator
                    updateCurrentFileIndicator();
                }
            });

            // Ctrl/Cmd+A inside preview selects only the preview contents
            $('#cfc-preview-highlight').on('keydown', function(e){
                const key = (e.key || '').toLowerCase();
                if ((e.ctrlKey || e.metaKey) && key === 'a') {
                    e.preventDefault();
                    const range = document.createRange();
                    range.selectNodeContents(this);
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            });
        }
    });

    // ---------- File-tree search (left pane) ----------

    async function handleFileSearch(query) {

        if (query === lastQuery) {
            return;
        }
        lastQuery = query;

        const $allLi = $('#cfc-directory-tree-container li');

        // Empty query resets tree
        if (!query) {
            $allLi.removeClass('cfc-ls-hidden');
            $('.cfc-ls-highlight').removeClass('cfc-ls-highlight');
            return;
        }

        let results = [];
        try {
            results = await $.ajax({
                url: CFC_LS_Settings.api_base + 'search',
                method: 'GET',
                data: { term: query },
                beforeSend: function(xhr){
                    xhr.setRequestHeader('X-WP-Nonce', CFC_LS_Settings.api_nonce);
                }
            });
        } catch (e) {
            console.error('CFC-LS Search API error:', e);
            return;
        }

        const expansions = new Set();
        const keep       = new Set();

        results.forEach(function(item){
            keep.add(item.path);
            const parts = item.path.split('/');
            parts.reduce(function(acc, seg, idx){
                const path = (idx === 0) ? seg : acc + '/' + seg;
                expansions.add(path);
                keep.add(path);
                return path;
            }, '');
        });

        await expandAncestors(Array.from(expansions));

        $('.cfc-ls-highlight').removeClass('cfc-ls-highlight');

        $allLi.each(function(){
            const rel = $(this)
                .find('span.cfc-v2-folder-name, span.cfc-v2-file-name')
                .data('rel');
            $(this).toggleClass('cfc-ls-hidden', !keep.has(rel));
        });

        results.forEach(function(item){
            const sel = item.is_dir ? '.cfc-v2-folder-name' : '.cfc-v2-file-name';
            $(sel + '[data-rel="' + item.path + '"]').addClass('cfc-ls-highlight');
        });

        if (results.length) {
            const first = results[0];
            const sel   = first.is_dir ? '.cfc-v2-folder-name' : '.cfc-v2-file-name';
            const node  = $(sel + '[data-rel="' + first.path + '"]')[0];
            if (node) {
                node.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    async function expandAncestors(paths) {
        for (let i = 0; i < paths.length; i++) {
            const path = paths[i];
            const span = document.querySelector('.cfc-v2-folder-name[data-rel="' + path + '"]');
            if (!span) {
                continue;
            }
            const li    = span.closest('li');
            const icon  = li.querySelector('.cfc-v2-expand-icon');
            const subUL = li.querySelector('ul.cfc-v2-tree');
            if (icon && subUL) {
                await expandFolder(icon, subUL);
            }
        }
    }

    function expandFolder(icon, subUL) {
        return new Promise(function(resolve){
            const obs = new MutationObserver(function(){
                if (subUL.children.length) {
                    obs.disconnect();
                    resolve();
                }
            });
            obs.observe(subUL, { childList: true });
            icon.click();
            setTimeout(function(){
                obs.disconnect();
                resolve();
            }, 3000);
        });
    }

    // ---------- Preview building (right pane) ----------

    function buildHighlightedPreview(text, container) {
        const sections = parseSections(text);
        sections.forEach(function(sec){
            renderSection(sec, container);
        });
    }

    /**
     * Break combined text into "sections" (summary + per-file blocks).
     * Normalises so that:
     * - Comment header lines (/*, * ) are moved from headers into code,
     *   so line numbers match the original file.
     * - "Functions: ..." banner stays in the header.
     */
    function parseSections(text) {
        const lines = text.split('\n');
        const secs  = [];
        let curr    = { headers: [], codeLines: [] };

        lines.forEach(function(line){
            if (line.startsWith('==========')) {
                // Start of a new file section.
                if (curr.headers.length || curr.codeLines.length) {
                    secs.push(curr);
                }
                curr = { headers: [ line ], codeLines: [] };
            } else if (curr.headers.length && curr.headers.length < 4) {
                // First few lines after the banner: treat as header/meta by default;
                // we'll move comment-style lines down into code below.
                curr.headers.push(line);
            } else {
                curr.codeLines.push(line);
            }
        });

        if (curr.headers.length || curr.codeLines.length) {
            secs.push(curr);
        }

        // Normalise each section
        secs.forEach(function(sec){
            sec.headers   = sec.headers || [];
            sec.codeLines = sec.codeLines || [];

            // (1) Move comment-style lines from headers into the front of codeLines.
            //     This ensures "/* File: ..." and "* Purpose: ..." are counted as
            //     real code lines for numbering.
            const newHeaders   = [];
            const headerAsCode = [];

            sec.headers.forEach(function(h){
                const t = String(h).trim();
                if (t.startsWith('/*') || t.startsWith('* ')) {
                    headerAsCode.push(h);
                } else {
                    newHeaders.push(h);
                }
            });

            sec.headers   = newHeaders;
            if (headerAsCode.length) {
                sec.codeLines = headerAsCode.concat(sec.codeLines);
            }

            // (2) Trim leading blank lines from code.
            while (sec.codeLines.length && String(sec.codeLines[0]).trim() === '') {
                sec.codeLines.shift();
            }

            // (3) If first nonblank code line is the Functions banner, keep it as header.
            if (
                sec.codeLines.length &&
                /^=+\s*\[\s*Functions:/i.test(String(sec.codeLines[0]).trim())
            ) {
                sec.headers.push(sec.codeLines[0]);
                sec.codeLines.shift();
                while (sec.codeLines.length && String(sec.codeLines[0]).trim() === '') {
                    sec.codeLines.shift();
                }
            }
        });

        return secs;
    }

    function renderSection(sec, container) {
        const hasHeaders   = Array.isArray(sec.headers) && sec.headers.length > 0;
        const firstHeader  = hasHeaders ? String(sec.headers[0]) : '';
        const isFileBanner = firstHeader.startsWith('==========');

        // Summary or malformed header – just dump as a single pre with no gutter.
        if (!isFileBanner) {
            const allLines = []
                .concat(sec.headers || [])
                .concat(sec.codeLines || []);
            if (!allLines.length) {
                return;
            }
            const preSum = document.createElement('pre');
            preSum.className = 'cfc-file-header';
            preSum.textContent = allLines.join('\n');
            container.appendChild(preSum);
            return;
        }

        // File header block.
        const hdr = document.createElement('pre');
        hdr.className = 'cfc-file-header';
        hdr.textContent = (sec.headers || []).join('\n');
        container.appendChild(hdr);

        // File code block with line numbers.
        const filePathMatch = firstHeader.match(/] (.+) =+$/);
        const filePath      = (filePathMatch || [])[1] || '';
        const ext           = filePath.split('.').pop().toLowerCase();
        const lang          =
            ['php','inc','phtml'].indexOf(ext) !== -1 ? 'php' :
            ['js','jsx'].indexOf(ext)   !== -1 ? 'javascript' :
            ext === 'css'                      ? 'css' :
            ['html','htm','xml'].indexOf(ext) !== -1 ? 'xml' :
            ext === 'json'                     ? 'json' :
            'plaintext';

        const pre  = document.createElement('pre');
        pre.classList.add('line-numbers');
        const code = document.createElement('code');
        code.className = 'language-' + lang;
        if (filePath) {
            code.setAttribute('data-file-path', filePath);
        }
        code.textContent = (sec.codeLines || []).join('\n');
        pre.appendChild(code);
        container.appendChild(pre);

        // Highlight + line numbers.
        try {
            hljs.highlightElement(code);
        } catch (e) {
            console.warn('hljs.highlightElement failed', e);
        }
        if (code.textContent.trim()) {
            try {
                hljs.lineNumbersBlock(code);
            } catch (e) {
                console.warn('lineNumbersBlock failed for', firstHeader, e);
            }
        }
    }

    // ---------- Code search within preview ----------

    function handleCodeSearch(term) {
        const normalized = term.trim();
        const $blocks    = $('#cfc-preview-highlight pre code');

        if (!normalized) {
            updateCount(0, 0);
            return $blocks.each(function(){
                if (this._origHTML) {
                    this.innerHTML = this._origHTML;
                }
            });
        }

        const rx    = new RegExp(normalized.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
        let total   = 0;
        let current = 0;

        $blocks.each(function(){
            if (!this._origHTML) {
                this._origHTML = this.innerHTML;
            }
            this.innerHTML = this._origHTML;
            const matches  = (this.textContent.match(rx) || []).length;
            if (matches) {
                this.innerHTML = this._origHTML.replace(rx, function(m){
                    return '<mark class="cfc-term-highlight">' + m + '</mark>';
                });
                if (!current) {
                    current = 1;
                }
            }
            total += matches;
        });

        updateCount(current, total);

        const firstMark = $('#cfc-preview-highlight mark.cfc-term-highlight')[0];
        if (firstMark) {
            firstMark.scrollIntoView({ behavior: 'smooth', block: 'center' });
            updateCurrentFileIndicator();
        }
    }

    function updateCount(curr, total) {
        $('#cfc-code-count').text(curr + '/' + total);
    }

    // ---------- Current file indicator ----------

    function updateCurrentFileIndicator() {
        const $container = $('#cfc-preview-highlight');
        const $indicator = $('#cfc-current-file-indicator');

        if (!$container.length || !$indicator.length) {
            return;
        }

        const container  = $container[0];
        const codes      = container.querySelectorAll('pre.line-numbers code[data-file-path]');
        if (!codes.length) {
            $indicator.text('');
            return;
        }

        const containerRect = container.getBoundingClientRect();
        const targetY       = containerRect.top + 40;

        let best      = null;
        let bestDelta = Infinity;

        for (let i = 0; i < codes.length; i++) {
            const r = codes[i].getBoundingClientRect();
            if (r.bottom < containerRect.top || r.top > containerRect.bottom) {
                continue;
            }
            const d = Math.abs(r.top - targetY);
            if (d < bestDelta) {
                bestDelta = d;
                best      = codes[i];
            }
        }

        // Fallback: last code block above the viewport
        if (!best) {
            for (let i = 0; i < codes.length; i++) {
                const r = codes[i].getBoundingClientRect();
                if (r.top <= containerRect.top) {
                    const d = containerRect.top - r.top;
                    if (d < bestDelta) {
                        bestDelta = d;
                        best      = codes[i];
                    }
                }
            }
        }

        if (best) {
            const filePath = best.getAttribute('data-file-path') || '(unknown file)';
            $indicator.text(filePath);
        }
    }

})(jQuery);
