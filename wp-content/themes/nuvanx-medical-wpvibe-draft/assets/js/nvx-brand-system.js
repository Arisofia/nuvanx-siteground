(function () {
  'use strict';

  function startHomeVideo() {
    var video = document.getElementById('nvx-home-hero-video');
    if (!video) return;

    var frame = video.closest('.nvx-home-video-frame');
    var source = video.querySelector('source');

    video.muted = true;
    video.defaultMuted = true;
    video.playsInline = true;
    video.autoplay = true;
    video.loop = true;
    video.setAttribute('muted', '');
    video.setAttribute('playsinline', '');
    video.setAttribute('autoplay', '');
    video.removeAttribute('hidden');

    if (source && !source.src && source.dataset.src) {
      source.src = source.dataset.src;
    }

    if (frame) {
      frame.classList.add('is-video-mounted');
    }

    function revealVideo() {
      video.classList.add('is-ready');
      if (frame) frame.classList.add('is-video-ready');
    }

    function tryPlay() {
      var promise = video.play();

      if (promise && typeof promise.then === 'function') {
        promise.then(revealVideo).catch(function () {
          if (frame) frame.classList.add('is-video-poster');
        });
      } else {
        revealVideo();
      }
    }

    video.addEventListener('loadeddata', revealVideo, { once: true });
    video.addEventListener('canplay', tryPlay, { once: true });

    try {
      video.load();
    } catch (error) {
      if (frame) frame.classList.add('is-video-poster');
    }

    if (video.readyState >= 2) {
      tryPlay();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startHomeVideo, { once: true });
  } else {
    startHomeVideo();
  }
})();
