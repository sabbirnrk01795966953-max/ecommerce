(() => {
  'use strict';

  const onReady = (fn) => {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  };

  const escapeAttr = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;');

  const normalizeVideoUrl = (rawUrl) => {
    const url = String(rawUrl || '').trim();
    if (!url) return null;

    try {
      const parsed = new URL(url, window.location.origin);
      const host = parsed.hostname.replace(/^www\./, '').toLowerCase();

      if (host === 'youtu.be') {
        const id = parsed.pathname.split('/').filter(Boolean)[0];
        if (id) return { type: 'iframe', url: `https://www.youtube.com/embed/${encodeURIComponent(id)}` };
      }

      if (host.endsWith('youtube.com')) {
        let id = parsed.searchParams.get('v');
        if (!id && parsed.pathname.includes('/embed/')) id = parsed.pathname.split('/embed/')[1]?.split('/')[0];
        if (!id && parsed.pathname.includes('/shorts/')) id = parsed.pathname.split('/shorts/')[1]?.split('/')[0];
        if (id) return { type: 'iframe', url: `https://www.youtube.com/embed/${encodeURIComponent(id)}` };
      }

      if (host === 'vimeo.com' || host.endsWith('.vimeo.com')) {
        const id = parsed.pathname.split('/').filter(Boolean).find((part) => /^\d+$/.test(part));
        if (id) return { type: 'iframe', url: `https://player.vimeo.com/video/${encodeURIComponent(id)}` };
      }

      return { type: 'video', url };
    } catch (_) {
      return null;
    }
  };

  class RichEditor {
    constructor(shell) {
      this.shell = shell;
      this.name = shell.dataset.editorShell;
      this.editor = shell.querySelector(`[data-editor="${this.name}"]`);
      this.hidden = shell.querySelector(`[data-editor-input="${this.name}"]`);
      this.toolbar = shell.querySelector(`[data-editor-toolbar="${this.name}"]`);
      this.savedRange = null;

      if (!this.editor || !this.hidden || !this.toolbar) return;

      try { document.execCommand('styleWithCSS', false, true); } catch (_) {}

      this.bind();
      this.sync();
    }

    bind() {
      this.editor.addEventListener('input', () => this.sync());
      this.editor.addEventListener('keyup', () => this.rememberSelection());
      this.editor.addEventListener('mouseup', () => this.rememberSelection());
      this.editor.addEventListener('focus', () => this.rememberSelection());
      this.editor.addEventListener('paste', () => setTimeout(() => this.sync(), 0));

      this.toolbar.addEventListener('mousedown', (event) => {
        if (event.target.closest('button')) event.preventDefault();
      });

      this.toolbar.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button) return;

        const command = button.dataset.command;
        const action = button.dataset.action;

        if (command) {
          this.restoreSelection();
          document.execCommand(command, false, null);
          this.editor.focus();
          this.sync();
          return;
        }

        if (action) this.runAction(action);
      });

      this.toolbar.addEventListener('change', (event) => {
        const control = event.target;
        const action = control.dataset.action;
        if (!action) return;

        this.restoreSelection();

        if (action === 'format-block') {
          document.execCommand('formatBlock', false, control.value);
        } else if (action === 'font-name') {
          document.execCommand('fontName', false, control.value);
        } else if (action === 'font-size') {
          this.applyInlineStyle('fontSize', control.value);
        } else if (action === 'line-height') {
          this.applyBlockStyle('lineHeight', control.value);
        } else if (action === 'text-color') {
          document.execCommand('foreColor', false, control.value);
        } else if (action === 'background-color') {
          document.execCommand('hiliteColor', false, control.value);
        }

        this.editor.focus();
        this.sync();
      });

      this.editor.closest('form')?.addEventListener('submit', () => this.sync());
    }

    rememberSelection() {
      const selection = window.getSelection();
      if (!selection || selection.rangeCount === 0) return;
      const range = selection.getRangeAt(0);
      if (this.editor.contains(range.commonAncestorContainer)) this.savedRange = range.cloneRange();
    }

    restoreSelection() {
      this.editor.focus();
      if (!this.savedRange) return;
      const selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(this.savedRange);
    }

    sync() {
      this.hidden.value = this.editor.innerHTML.trim();
    }

    insertHtml(html) {
      this.restoreSelection();
      document.execCommand('insertHTML', false, html);
      this.editor.focus();
      this.sync();
      this.rememberSelection();
    }

    applyInlineStyle(property, value) {
      const selection = window.getSelection();
      if (!selection || selection.rangeCount === 0) return;
      const range = selection.getRangeAt(0);

      if (range.collapsed) {
        const span = document.createElement('span');
        span.style[property] = value;
        span.appendChild(document.createTextNode('\u200B'));
        range.insertNode(span);
        range.setStart(span.firstChild, 1);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
        this.savedRange = range.cloneRange();
        return;
      }

      const span = document.createElement('span');
      span.style[property] = value;
      try {
        range.surroundContents(span);
      } catch (_) {
        const fragment = range.extractContents();
        span.appendChild(fragment);
        range.insertNode(span);
      }
      selection.removeAllRanges();
      const nextRange = document.createRange();
      nextRange.selectNodeContents(span);
      selection.addRange(nextRange);
      this.savedRange = nextRange.cloneRange();
    }

    applyBlockStyle(property, value) {
      const selection = window.getSelection();
      if (!selection || selection.rangeCount === 0) return;
      let node = selection.anchorNode;
      if (!node) return;
      if (node.nodeType === Node.TEXT_NODE) node = node.parentElement;

      const blockSelector = 'p,div,h1,h2,h3,h4,h5,h6,blockquote,li,pre,td,th';
      let block = node?.closest?.(blockSelector);
      if (!block || !this.editor.contains(block)) {
        document.execCommand('formatBlock', false, 'p');
        node = window.getSelection()?.anchorNode;
        if (node?.nodeType === Node.TEXT_NODE) node = node.parentElement;
        block = node?.closest?.(blockSelector);
      }

      if (block && this.editor.contains(block)) block.style[property] = value;
    }

    runAction(action) {
      this.rememberSelection();

      if (action === 'undo' || action === 'redo') {
        this.restoreSelection();
        document.execCommand(action, false, null);
        this.sync();
        return;
      }

      if (action === 'link') {
        const url = window.prompt('Link URL (https://...)');
        if (!url) return;
        this.restoreSelection();
        document.execCommand('createLink', false, url.trim());
        this.editor.querySelectorAll('a').forEach((a) => {
          if (/^https?:\/\//i.test(a.getAttribute('href') || '')) {
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
          }
        });
        this.sync();
        return;
      }

      if (action === 'image-url') {
        const url = window.prompt('Image URL');
        if (!url) return;
        const alt = window.prompt('Image alt text (optional)') || '';
        this.insertHtml(`<figure class="editor-media"><img src="${escapeAttr(url.trim())}" alt="${escapeAttr(alt)}" loading="lazy"></figure><p><br></p>`);
        return;
      }

      if (action === 'video-url') {
        const raw = window.prompt('YouTube, Vimeo or direct MP4/WebM video URL');
        const video = normalizeVideoUrl(raw);
        if (!video) return;

        if (video.type === 'iframe') {
          this.insertHtml(`<div class="editor-video-wrap"><iframe src="${escapeAttr(video.url)}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div><p><br></p>`);
        } else {
          this.insertHtml(`<div class="editor-video-wrap"><video controls preload="metadata" src="${escapeAttr(video.url)}"></video></div><p><br></p>`);
        }
        return;
      }

      if (action === 'image-upload') {
        window.activeRichEditor = this;
        const input = document.getElementById('editorImageUpload');
        if (input) {
          input.value = '';
          input.click();
        }
        return;
      }

      if (action === 'video-upload') {
        window.activeRichEditor = this;
        const input = document.getElementById('editorVideoUpload');
        if (input) {
          input.value = '';
          input.click();
        }
        return;
      }

      if (action === 'html') {
        window.activeRichEditor = this;
        openHtmlModal();
        return;
      }

      if (action === 'fullscreen') {
        this.shell.classList.toggle('editor-fullscreen');
        document.body.classList.toggle('editor-fullscreen-open', this.shell.classList.contains('editor-fullscreen'));
        this.editor.focus();
      }
    }
  }

  const uploadEditorMedia = async (file, expectedType) => {
    const endpoint = window.productEditorMediaUploadUrl;
    if (!endpoint) throw new Error('Editor media upload endpoint is missing.');

    const formData = new FormData();
    formData.append('file', file);
    formData.append('expected_type', expectedType);

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Accept': 'application/json',
      },
      body: formData,
      credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const validationMessage = payload?.errors?.file?.[0];
      throw new Error(validationMessage || payload?.message || 'Upload failed.');
    }

    return payload;
  };

  const bindMediaInput = (id, expectedType) => {
    const input = document.getElementById(id);
    if (!input) return;

    input.addEventListener('change', async () => {
      const file = input.files?.[0];
      const editor = window.activeRichEditor;
      if (!file || !editor) return;

      if (expectedType === 'video') {
        const limitMessage = fileUploadLimitMessage(file);
        if (limitMessage) {
          window.alert(limitMessage);
          input.value = '';
          return;
        }
      }

      const button = editor.toolbar.querySelector(`[data-action="${expectedType}-upload"]`);
      const oldText = button?.textContent;
      if (button) {
        button.disabled = true;
        button.textContent = 'Uploading…';
      }

      try {
        const uploaded = await uploadEditorMedia(file, expectedType);
        if (uploaded.type === 'image') {
          editor.insertHtml(`<figure class="editor-media"><img src="${escapeAttr(uploaded.url)}" alt="" loading="lazy"></figure><p><br></p>`);
        } else {
          editor.insertHtml(`<div class="editor-video-wrap"><video controls preload="metadata" src="${escapeAttr(uploaded.url)}"></video></div><p><br></p>`);
        }
      } catch (error) {
        window.alert(error?.message || 'Upload failed.');
      } finally {
        if (button) {
          button.disabled = false;
          button.textContent = oldText;
        }
        input.value = '';
      }
    });
  };


  const formatBytes = (bytes) => {
    const value = Number(bytes || 0);
    if (!value) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1);
    const number = value / Math.pow(1024, index);
    return `${number >= 10 || index === 0 ? number.toFixed(0) : number.toFixed(1)} ${units[index]}`;
  };

  const fileUploadLimitMessage = (file) => {
    const config = window.productUploadConfig || {};
    const appVideoMax = Number(config.appVideoMaxBytes || 50 * 1024 * 1024);
    const uploadMax = Number(config.uploadMaxBytes || 0);
    const postMax = Number(config.postMaxBytes || 0);
    const hardLimit = [appVideoMax, uploadMax, postMax].filter((n) => n > 0).reduce((a, b) => Math.min(a, b), Infinity);

    if (file.size > appVideoMax) {
      return `ভিডিওটি ${formatBytes(file.size)}। Product video সর্বোচ্চ ${formatBytes(appVideoMax)} হতে পারবে।`;
    }

    if (Number.isFinite(hardLimit) && file.size > hardLimit) {
      return `ভিডিওটি ${formatBytes(file.size)}, কিন্তু বর্তমান PHP/server limit প্রায় ${formatBytes(hardLimit)}। তাই file Laravel-এ পৌঁছানোর আগেই upload fail করবে। Local test-এর জন্য PHP upload_max_filesize এবং post_max_size বাড়াতে হবে।`;
    }

    return '';
  };

  const setupSingleImagePreview = () => {
    const input = document.getElementById('mainImageInput');
    const box = document.getElementById('mainImagePreviewBox');
    const image = document.getElementById('mainImagePreview');
    const info = document.getElementById('mainImageInfo');
    const empty = box?.querySelector('.media-empty-state');
    if (!input || !box || !image || !info || !empty) return;

    let objectUrl = null;
    input.addEventListener('change', () => {
      const file = input.files?.[0];
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = null;

      if (!file) return;
      if (!file.type.startsWith('image/')) {
        window.alert('Main image হিসেবে একটি valid image select করুন।');
        input.value = '';
        return;
      }

      objectUrl = URL.createObjectURL(file);
      image.src = objectUrl;
      image.hidden = false;
      empty.hidden = true;
      info.hidden = false;
      info.innerHTML = `<span>${escapeAttr(file.name)}</span><b>${formatBytes(file.size)}</b>`;
      box.classList.add('has-media');
    });
  };

  const setupProductVideoPreview = () => {
    const input = document.getElementById('productVideoInput');
    const box = document.getElementById('productVideoPreviewBox');
    const video = document.getElementById('productVideoPreview');
    const info = document.getElementById('productVideoInfo');
    const clear = document.getElementById('clearProductVideoSelection');
    const empty = box?.querySelector('.media-empty-state');
    if (!input || !box || !video || !info || !clear || !empty) return;

    const existingSrc = box.dataset.existingSrc || '';
    let objectUrl = null;

    const restoreExisting = () => {
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = null;
      input.value = '';
      clear.hidden = true;

      if (existingSrc) {
        video.src = existingSrc;
        video.hidden = false;
        empty.hidden = true;
        info.hidden = false;
        info.innerHTML = '<span>বর্তমান uploaded video</span>';
        box.classList.add('has-media');
      } else {
        video.pause();
        video.removeAttribute('src');
        video.load();
        video.hidden = true;
        info.hidden = true;
        empty.hidden = false;
        box.classList.remove('has-media');
      }
    };

    input.addEventListener('change', () => {
      const file = input.files?.[0];
      if (!file) return;

      const extension = (file.name.split('.').pop() || '').toLowerCase();
      if (!['mp4', 'webm', 'mov'].includes(extension)) {
        window.alert('Video শুধু MP4, WebM অথবা MOV হতে পারবে।');
        restoreExisting();
        return;
      }

      const limitMessage = fileUploadLimitMessage(file);
      if (limitMessage) {
        window.alert(limitMessage);
        restoreExisting();
        return;
      }

      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = URL.createObjectURL(file);
      video.src = objectUrl;
      video.hidden = false;
      empty.hidden = true;
      info.hidden = false;
      info.innerHTML = `<span>${escapeAttr(file.name)}</span><b>${formatBytes(file.size)}</b>`;
      clear.hidden = false;
      box.classList.add('has-media');
      video.load();
    });

    clear.addEventListener('click', restoreExisting);
  };

  const setupVideoUrlPreview = () => {
    const input = document.getElementById('productVideoUrl');
    const button = document.getElementById('previewVideoUrlButton');
    const preview = document.getElementById('videoUrlPreview');
    if (!input || !button || !preview) return;

    button.addEventListener('click', () => {
      const normalized = normalizeVideoUrl(input.value);
      preview.innerHTML = '';
      preview.hidden = true;

      if (!normalized) {
        window.alert('Valid video URL দিন।');
        return;
      }

      if (normalized.type === 'iframe') {
        preview.innerHTML = `<iframe src="${escapeAttr(normalized.url)}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;
      } else {
        preview.innerHTML = `<video controls preload="metadata" src="${escapeAttr(normalized.url)}"></video>`;
      }
      preview.hidden = false;
    });
  };

  const setupGalleryOrganizer = () => {
    const input = document.getElementById('galleryInput');
    const organizer = document.getElementById('galleryOrganizer');
    const orderInput = document.getElementById('galleryOrderInput');
    const empty = document.getElementById('galleryEmptyState');
    const countLabel = document.getElementById('galleryCountLabel');
    if (!input || !organizer || !orderInput || !empty) return;

    const newFiles = new Map();
    let dragged = null;
    let counter = 0;

    const getItems = () => Array.from(organizer.querySelectorAll('.gallery-sort-item'));

    const syncGallery = () => {
      const items = getItems();
      const orderedNewCards = items.filter((item) => item.dataset.newFileId);
      const dt = new DataTransfer();
      const newIndex = new Map();

      orderedNewCards.forEach((card, index) => {
        const data = newFiles.get(card.dataset.newFileId);
        if (!data) return;
        dt.items.add(data.file);
        newIndex.set(card.dataset.newFileId, index);
      });

      try { input.files = dt.files; } catch (_) {}

      const sequence = [];
      items.forEach((item, index) => {
        item.querySelector('.gallery-order-badge').textContent = String(index + 1);
        if (item.dataset.galleryKey) sequence.push(item.dataset.galleryKey);
        else if (item.dataset.newFileId && newIndex.has(item.dataset.newFileId)) sequence.push(`new:${newIndex.get(item.dataset.newFileId)}`);
      });

      orderInput.value = JSON.stringify(sequence);
      empty.hidden = items.length > 0;
      if (countLabel) countLabel.textContent = items.length ? `${items.length} image${items.length === 1 ? '' : 's'}` : '';
    };

    const moveCard = (card, direction) => {
      if (!card) return;
      if (direction === 'left') {
        const previous = card.previousElementSibling;
        if (previous) organizer.insertBefore(card, previous);
      } else {
        const next = card.nextElementSibling;
        if (next) organizer.insertBefore(next, card);
      }
      syncGallery();
    };

    const bindCard = (card) => {
      card.setAttribute('draggable', 'true');

      card.addEventListener('dragstart', (event) => {
        dragged = card;
        card.classList.add('is-dragging');
        if (event.dataTransfer) {
          event.dataTransfer.effectAllowed = 'move';
          event.dataTransfer.setData('text/plain', card.dataset.galleryKey || card.dataset.newFileId || 'gallery-item');
        }
      });

      card.addEventListener('dragend', () => {
        card.classList.remove('is-dragging');
        dragged = null;
        syncGallery();
      });

      card.querySelectorAll('[data-gallery-move]').forEach((button) => {
        button.addEventListener('click', () => moveCard(card, button.dataset.galleryMove));
      });

      const removeButton = card.querySelector('[data-gallery-remove-new]');
      removeButton?.addEventListener('click', () => {
        const id = card.dataset.newFileId;
        const data = newFiles.get(id);
        if (data?.url) URL.revokeObjectURL(data.url);
        newFiles.delete(id);
        card.remove();
        syncGallery();
      });
    };

    organizer.addEventListener('dragover', (event) => {
      if (!dragged) return;
      event.preventDefault();
      const target = document.elementFromPoint(event.clientX, event.clientY)?.closest?.('.gallery-sort-item');
      if (!target || target === dragged || !organizer.contains(target)) return;

      const rect = target.getBoundingClientRect();
      const before = event.clientX < rect.left + rect.width / 2;
      organizer.insertBefore(dragged, before ? target : target.nextSibling);
    });

    getItems().forEach(bindCard);

    input.addEventListener('change', () => {
      // Selecting again replaces only the unsaved/new gallery files; existing saved images stay untouched.
      organizer.querySelectorAll('[data-new-file-id]').forEach((card) => {
        const data = newFiles.get(card.dataset.newFileId);
        if (data?.url) URL.revokeObjectURL(data.url);
        card.remove();
      });
      newFiles.clear();

      Array.from(input.files || []).forEach((file) => {
        if (!file.type.startsWith('image/')) return;
        const id = `gallery-new-${Date.now()}-${counter++}`;
        const url = URL.createObjectURL(file);
        newFiles.set(id, { file, url });

        const card = document.createElement('div');
        card.className = 'gallery-sort-item gallery-sort-item-new';
        card.dataset.newFileId = id;
        card.innerHTML = `
          <div class="gallery-order-badge">0</div>
          <div class="gallery-drag-handle" title="Drag to reorder">⠿</div>
          <img src="${escapeAttr(url)}" alt="Selected gallery image">
          <div class="gallery-card-meta"><span>${escapeAttr(file.name)}</span><small>${formatBytes(file.size)}</small></div>
          <div class="gallery-card-actions">
            <button type="button" class="gallery-move" data-gallery-move="left" title="Move left">←</button>
            <button type="button" class="gallery-move" data-gallery-move="right" title="Move right">→</button>
            <button type="button" class="gallery-delete" data-gallery-remove-new title="Remove selected image">×</button>
          </div>`;
        organizer.appendChild(card);
        bindCard(card);
      });

      syncGallery();
    });

    syncGallery();
  };

  const setupProductUploadBudgetCheck = () => {
    const form = document.querySelector('form.product-form');
    if (!form) return;

    form.addEventListener('submit', (event) => {
      const config = window.productUploadConfig || {};
      const postMax = Number(config.postMaxBytes || 0);
      const uploadMax = Number(config.uploadMaxBytes || 0);
      const inputs = ['mainImageInput', 'galleryInput', 'productVideoInput']
        .map((id) => document.getElementById(id))
        .filter(Boolean);
      const files = inputs.flatMap((input) => Array.from(input.files || []));
      const total = files.reduce((sum, file) => sum + file.size, 0);

      const tooLargeSingle = uploadMax > 0 ? files.find((file) => file.size > uploadMax) : null;
      if (tooLargeSingle) {
        event.preventDefault();
        window.alert(`“${tooLargeSingle.name}” file টি ${formatBytes(tooLargeSingle.size)}, কিন্তু PHP upload_max_filesize ${formatBytes(uploadMax)}। Server limit বাড়ানো ছাড়া upload হবে না।`);
        return;
      }

      if (postMax > 0 && total > postMax * 0.95) {
        event.preventDefault();
        window.alert(`এই form-এর selected media মোট প্রায় ${formatBytes(total)}, কিন্তু PHP post_max_size ${formatBytes(postMax)}। post_max_size বাড়ান অথবা কম file upload করুন।`);
      }
    }, { capture: true });
  };

  const setupProductMediaManager = () => {
    setupSingleImagePreview();
    setupProductVideoPreview();
    setupVideoUrlPreview();
    setupGalleryOrganizer();
    setupProductUploadBudgetCheck();
  };

  const openHtmlModal = () => {
    const modal = document.getElementById('editorHtmlModal');
    const textarea = document.getElementById('editorHtmlCode');
    if (!modal || !textarea) return;
    textarea.value = '';
    modal.hidden = false;
    document.body.classList.add('modal-open');
    setTimeout(() => textarea.focus(), 30);
  };

  const closeHtmlModal = () => {
    const modal = document.getElementById('editorHtmlModal');
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('modal-open');
  };

  onReady(() => {
    const editors = Array.from(document.querySelectorAll('[data-editor-shell]')).map((shell) => new RichEditor(shell));
    window.productRichEditors = editors;

    bindMediaInput('editorImageUpload', 'image');
    bindMediaInput('editorVideoUpload', 'video');
    setupProductMediaManager();

    document.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', closeHtmlModal));

    document.getElementById('insertEditorHtml')?.addEventListener('click', () => {
      const html = document.getElementById('editorHtmlCode')?.value || '';
      if (html.trim() && window.activeRichEditor) window.activeRichEditor.insertHtml(html);
      closeHtmlModal();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        const fullscreen = document.querySelector('.editor-fullscreen');
        if (fullscreen) {
          fullscreen.classList.remove('editor-fullscreen');
          document.body.classList.remove('editor-fullscreen-open');
          return;
        }
        closeHtmlModal();
      }
    });
  });
})();
