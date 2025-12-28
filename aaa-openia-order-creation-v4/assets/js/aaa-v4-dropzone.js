// File: /aaa-openia-order-creation-v4/assets/js/aaa-v4-dropzone.js

document.addEventListener('DOMContentLoaded', () => {
  const ajaxUrl =
    (window.AAA_V4_DROPZONE && AAA_V4_DROPZONE.url) ||
    (typeof window.ajaxurl === 'string' ? window.ajaxurl : '') ||
    '';

  const userId =
    (window.AAA_V4_DROPZONE && AAA_V4_DROPZONE.user_id) || 0;

  if (!ajaxUrl) {
    console.warn('[Dropzone] No AJAX URL available. Uploads disabled.');
    return;
  }

  document.querySelectorAll('.dropzone').forEach((zone) => {
    const input = zone.querySelector('input[type="file"]');
    const preview = zone.querySelector('.preview');

    if (!input) return;

    const uploadFile = (file) => {
      console.log(
        '[Dropzone] Preparing to upload file:',
        file?.name,
        'size:',
        file?.size,
        'field:',
        zone.dataset.field
      );

      const formData = new FormData();
      formData.append('action', 'aaa_v4_upload_image');
      formData.append('upload_field', zone.dataset.field || '');
      formData.append('file', file);

      // Required by PHP handler for meta save
      if (userId) {
        formData.append('user_id', String(userId));
        console.log('[Dropzone] Attached user_id:', userId);
      } else {
        console.warn('[Dropzone] No user_id available.');
      }

      fetch(ajaxUrl, { method: 'POST', body: formData })
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
            if (preview) {
              preview.src = data.data.url;
              preview.style.display = 'block';
            }
          } else {
            console.error('[Dropzone] Upload failed. Response object:', data);
            alert('Upload failed: ' + (data.data?.message || data.message || 'Unknown error'));
          }
        })
        .catch((err) => {
          console.error('[Dropzone] Fetch/AJAX error:', err);
          alert('AJAX error: ' + err);
        });
    };

    input.addEventListener('change', (e) => {
      if (e.target.files && e.target.files[0]) {
        console.log('[Dropzone] File selected via chooser:', e.target.files[0].name);
        uploadFile(e.target.files[0]);
      } else {
        console.warn('[Dropzone] File chooser closed without selecting a file.');
      }
    });

    ['dragover', 'dragenter'].forEach((eventName) => {
      zone.addEventListener(eventName, (e) => {
        e.preventDefault();
        zone.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
      zone.addEventListener(eventName, (e) => {
        e.preventDefault();
        zone.classList.remove('dragover');
      });
    });

    zone.addEventListener('drop', (e) => {
      e.preventDefault();
      zone.classList.remove('dragover');

      const file = e.dataTransfer.files && e.dataTransfer.files[0];
      if (file) {
        console.log('[Dropzone] File dropped:', file.name, 'size:', file.size);
        uploadFile(file);
      } else {
        console.warn('[Dropzone] Drop event had no file.');
      }
    });
  });
});
