(function () {
  function startHomeVideo() {
    let video = document.getElementById('nvx-home-hero-video');
    if (!video) return;

    video.muted = true;
    video.playsInline = true;
    video.setAttribute('muted', '');
    video.setAttribute('playsinline', '');
    video.setAttribute('autoplay', '');

    let frame = video.closest('.nvx-home-video-frame');
    if (frame) frame.classList.add('is-video-mounted');

    function tryPlay() {
      let p = video.play();
      if (p && typeof p.catch === 'function') {
        p.catch(function () {
          if (frame) frame.classList.add('is-video-poster');
        });
      }
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
