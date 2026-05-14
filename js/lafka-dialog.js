/* lafka-theme/js/lafka-dialog.js
 * Lightweight native-<dialog> wrapper. Replacement for Magnific Popup
 * (P3-04) — drops jQuery dependency + 27 KB of vendor code in favor
 * of the browser's built-in modal primitive.
 *
 * Public API (window.lafkaDialog):
 *   lafkaDialog.image(src, opts)                — single-image lightbox
 *   lafkaDialog.gallery(items, startIndex, opts) — image gallery with prev/next
 *   lafkaDialog.inline(html, opts)              — modal containing arbitrary HTML
 *   lafkaDialog.iframe(url, opts)               — modal embedding an iframe (videos)
 *   lafkaDialog.close()                         — close whatever is open
 *
 * Each method returns the underlying <dialog> element so callers can
 * attach extra event listeners (e.g. quickview hooks variation forms
 * after the modal opens — see lafka-libs-config.js quickview migration).
 *
 * TRUST: inline(html) is the only entry point that takes HTML strings.
 * Callers MUST pass trusted markup (e.g. server-rendered WC AJAX). HTML
 * is parsed inside an inert <template> element so any inline <script>
 * tags do not execute, but other XSS vectors (event handler attrs etc.)
 * are still possible — callers are responsible for sanitization.
 *
 * @since 5.22.0
 */
(function () {
	'use strict';

	if (window.lafkaDialog) {
		return;
	}

	var current = null;

	function makeDialog(extraClass) {
		var dlg = document.createElement('dialog');
		dlg.className = 'lafka-dialog' + (extraClass ? ' ' + extraClass : '');
		dlg.setAttribute('aria-modal', 'true');

		var closeBtn = document.createElement('button');
		closeBtn.type = 'button';
		closeBtn.className = 'lafka-dialog__close';
		closeBtn.setAttribute('aria-label', 'Close');
		closeBtn.textContent = '×'; // multiplication sign as close glyph
		closeBtn.addEventListener('click', function () { close(dlg); });

		var content = document.createElement('div');
		content.className = 'lafka-dialog__content';

		dlg.appendChild(closeBtn);
		dlg.appendChild(content);
		dlg.__content = content;

		// Backdrop click closes (clicks on the dialog element itself,
		// not its children, mean the user clicked the backdrop area).
		dlg.addEventListener('click', function (e) {
			if (e.target === dlg) { close(dlg); }
		});

		// ESC fires the dialog's native cancel event. Hook to also unmount.
		dlg.addEventListener('cancel', function (e) {
			e.preventDefault();
			close(dlg);
		});

		return dlg;
	}

	function open(dlg) {
		if (current && current !== dlg) { close(current); }
		document.body.appendChild(dlg);
		current = dlg;
		if (typeof dlg.showModal === 'function') {
			dlg.showModal();
		} else {
			dlg.setAttribute('open', '');
			dlg.style.display = 'block';
		}
		document.dispatchEvent(new CustomEvent('lafka-dialog:open', { detail: { dialog: dlg } }));
	}

	function close(dlg) {
		dlg = dlg || current;
		if (!dlg) { return; }
		if (typeof dlg.close === 'function') {
			try { dlg.close(); } catch { /* fallback below */ }
		}
		dlg.removeAttribute('open');
		if (dlg.parentNode) { dlg.parentNode.removeChild(dlg); }
		if (current === dlg) { current = null; }
		document.dispatchEvent(new CustomEvent('lafka-dialog:close', { detail: { dialog: dlg } }));
	}

	function image(src, opts) {
		opts = opts || {};
		var dlg = makeDialog('lafka-dialog--image');
		var img = document.createElement('img');
		img.src = src;
		img.alt = opts.alt || '';
		img.className = 'lafka-dialog__image';
		dlg.__content.appendChild(img);
		open(dlg);
		return dlg;
	}

	function gallery(items, startIndex) {
		if (!items || !items.length) { return null; }
		var index = startIndex || 0;
		var dlg = makeDialog('lafka-dialog--gallery');

		var img = document.createElement('img');
		img.className = 'lafka-dialog__image';
		img.alt = '';
		dlg.__content.appendChild(img);

		var caption = document.createElement('div');
		caption.className = 'lafka-dialog__caption';
		dlg.__content.appendChild(caption);

		function render() {
			var it = items[index];
			img.src = it.src;
			img.alt = it.alt || '';
			caption.textContent = it.title || '';
			prevBtn.hidden = items.length < 2;
			nextBtn.hidden = items.length < 2;
		}

		var prevBtn = document.createElement('button');
		prevBtn.type = 'button';
		prevBtn.className = 'lafka-dialog__nav lafka-dialog__nav--prev';
		prevBtn.setAttribute('aria-label', 'Previous image');
		prevBtn.textContent = '‹'; // single left-pointing angle quote
		prevBtn.addEventListener('click', function () {
			index = (index - 1 + items.length) % items.length;
			render();
		});

		var nextBtn = document.createElement('button');
		nextBtn.type = 'button';
		nextBtn.className = 'lafka-dialog__nav lafka-dialog__nav--next';
		nextBtn.setAttribute('aria-label', 'Next image');
		nextBtn.textContent = '›'; // single right-pointing angle quote
		nextBtn.addEventListener('click', function () {
			index = (index + 1) % items.length;
			render();
		});

		dlg.appendChild(prevBtn);
		dlg.appendChild(nextBtn);

		dlg.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') { prevBtn.click(); }
			if (e.key === 'ArrowRight') { nextBtn.click(); }
		});

		render();
		open(dlg);
		return dlg;
	}

	function inline(html, opts) {
		opts = opts || {};
		var dlg = makeDialog('lafka-dialog--inline' + (opts.className ? ' ' + opts.className : ''));
		if (typeof html === 'string') {
			// Parse into an inert <template> so <script> tags don't execute
			// (event handler attributes and other XSS vectors are still
			// possible — see TRUST note at top of file).
			var tpl = document.createElement('template');
			tpl.innerHTML = html;
			dlg.__content.appendChild(tpl.content.cloneNode(true));
		} else if (html && html.nodeType === 1) {
			dlg.__content.appendChild(html);
		}
		open(dlg);
		return dlg;
	}

	function iframe(url) {
		var dlg = makeDialog('lafka-dialog--iframe');
		var frame = document.createElement('iframe');
		frame.src = toEmbedUrl(url);
		frame.setAttribute('allowfullscreen', '');
		frame.setAttribute('allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture');
		frame.setAttribute('frameborder', '0');
		frame.className = 'lafka-dialog__iframe';
		dlg.__content.appendChild(frame);
		open(dlg);
		return dlg;
	}

	// Map common video URLs (YouTube watch links, Vimeo) to embed URLs.
	// Direct file URLs (.mp4, .mov) pass through unchanged.
	function toEmbedUrl(url) {
		var yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&?#]+)/);
		if (yt) { return 'https://www.youtube.com/embed/' + yt[1]; }
		var vimeo = url.match(/vimeo\.com\/(\d+)/);
		if (vimeo) { return 'https://player.vimeo.com/video/' + vimeo[1]; }
		return url;
	}

	window.lafkaDialog = {
		image: image,
		gallery: gallery,
		inline: inline,
		iframe: iframe,
		close: function () { close(); }
	};
})();
