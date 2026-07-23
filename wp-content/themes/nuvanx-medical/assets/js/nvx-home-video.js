(function () {
  function startHomeVideo() {
    var video = document.getElementById('nvx-home-hero-video');
    if (!video) return;

    video.muted = true;
    video.playsInline = true;
    video.setAttribute('muted', '');
    video.setAttribute('playsinline', '');
    video.setAttribute('autoplay', '');

    var frame = video.closest('.nvx-home-video-frame');
    if (frame) frame.classList.add('is-video-mounted');

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var pauseBtn = document.getElementById('nvx-home-hero-video-pause');
    var isPlaying = false;

    function updateButtonState() {
      if (!pauseBtn) return;
      if (isPlaying) {
        pauseBtn.setAttribute('aria-label', 'Pause background video');
        pauseBtn.textContent = 'Pause';
      } else {
        pauseBtn.setAttribute('aria-label', 'Play background video');
        pauseBtn.textContent = 'Play';
      }
    }

    function tryPlay() {
      if (reduceMotion.matches) {
        video.pause();
        isPlaying = false;
        updateButtonState();
        return;
      }

      var p = video.play();
      if (p && typeof p.catch === 'function') {
        p.then(function () {
          isPlaying = true;
          updateButtonState();
        }).catch(function () {
          isPlaying = false;
          updateButtonState();
          if (frame) frame.classList.add('is-video-poster');
        });
      }
    }

    function handleReducedMotionChange() {
      if (reduceMotion.matches) {
        video.pause();
        isPlaying = false;
        updateButtonState();
      } else if (!isPlaying) {
        tryPlay();
      }
    }

    if (pauseBtn) {
      pauseBtn.addEventListener('click', function () {
        if (isPlaying) {
          video.pause();
          isPlaying = false;
        } else {
          var p = video.play();
          if (p && typeof p.catch === 'function') {
            p.then(function () {
              isPlaying = true;
              updateButtonState();
            }).catch(function () {
              isPlaying = false;
              updateButtonState();
            });
          }
        }
        updateButtonState();
      });
    }

    if (reduceMotion.addEventListener) {
      reduceMotion.addEventListener('change', handleReducedMotionChange);
    }

    if (video.readyState >= 2) {
      tryPlay();
    } else {
      video.addEventListener('loadeddata', tryPlay, { once: true });
      video.load();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startHomeVideo);
  } else {
    startHomeVideo();
  }
})();
