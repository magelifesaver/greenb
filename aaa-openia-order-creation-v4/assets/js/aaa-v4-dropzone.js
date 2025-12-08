// File: /aaa-openia-order-creation-v4/assets/js/aaa-v4-dropzone.js

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.dropzone').forEach(zone => {
        const input = zone.querySelector('input[type="file"]');
        const preview = zone.querySelector('.preview');

        const uploadFile = (file) => {
            console.log('[Dropzone] Preparing to upload file:', file?.name, 'size:', file?.size, 'field:', zone.dataset.field);

            const formData = new FormData();
            formData.append('action', 'aaa_v4_upload_image');
            formData.append('upload_field', zone.dataset.field);
            formData.append('file', file);

            // Required: pass user_id for meta save
            if (typeof ajaxurl.user_id !== 'undefined') {
                formData.append('user_id', ajaxurl.user_id);
                console.log('[Dropzone] Attached user_id:', ajaxurl.user_id);
            } else {
                console.warn('[Dropzone] No user_id found in ajaxurl!');
            }

            fetch(ajaxurl.url, {
                method: 'POST',
                body: formData,
            })
            .then(async (res) => {
                const rawText = await res.text();
                console.log('[Dropzone] Raw server response:', rawText);

                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (err) {
                    console.error('[Dropzone] Failed to parse JSON:', err, rawText);
                    alert('Upload failed: Invalid JSON response');
                    return;
                }

                if (data.success && data.data && data.data.url) {
                    console.log('[Dropzone] Upload success. File available at:', data.data.url);
                    preview.src = data.data.url;
                    preview.style.display = 'block';
                } else {
                    console.error('[Dropzone] Upload failed. Response object:', data);
                    alert('Upload failed: ' + (data.data?.message || data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('[Dropzone] Fetch/AJAX error:', err);
                alert('AJAX error: ' + err);
            });
        };

        // Removed auto-trigger (zone click no longer forces input.click)

        input.addEventListener('change', (e) => {
            if (e.target.files[0]) {
                console.log('[Dropzone] File selected via chooser:', e.target.files[0].name);
                uploadFile(e.target.files[0]);
            } else {
                console.warn('[Dropzone] File chooser closed without selecting a file.');
            }
        });

        ['dragover', 'dragenter'].forEach(eventName => {
            zone.addEventListener(eventName, e => {
                e.preventDefault();
                zone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, e => {
                e.preventDefault();
                zone.classList.remove('dragover');
            });
        });

        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('dragover');

            const file = e.dataTransfer.files[0];
            if (file) {
                console.log('[Dropzone] File dropped:', file.name, 'size:', file.size);
                uploadFile(file);
            } else {
                console.warn('[Dropzone] Drop event had no file.');
            }
        });
    });
});
