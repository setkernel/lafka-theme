/*! Modernizr custom build — touch detection only
 * Replaces Modernizr 2.7.0 custom build.
 * Provides: html.js, html.touch / html.no-touch classes,
 *           window.Modernizr.touch boolean.
 */
(function(window, document) {
  var docEl = document.documentElement;
  // Replace no-js with js
  docEl.className = docEl.className.replace(/(^|\s)no-js(\s|$)/, '$1js$2');
  // Touch detection
  var isTouch = ('ontouchstart' in window) || (window.navigator.maxTouchPoints > 0);
  docEl.className += isTouch ? ' touch' : ' no-touch';
  // Expose Modernizr-compatible object
  window.Modernizr = { touch: isTouch };
})(window, document);
