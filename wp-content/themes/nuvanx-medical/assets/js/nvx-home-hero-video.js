(() => {
  'use strict';

  const init = () => {
    const video = document.querySelector('.nvx-home-hero__video');
    const toggle = document.querySelector('[data-nvx-home-video-toggle]');
    const label = toggle?.querySelector('[data-nvx-home-video-label]');

    if (!(video instanceof HTMLVideoElement) || !(toggle instanceof HTMLButtonElement) || !label) {
      return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const sync = () => {
      const isPlaying = !video.paused && !video.ended;
      toggle.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
      toggle.setAttribute('aria-label', isPlaying ? 'Pausar vídeo de portada' : 'Reproducir vídeo de portada');
      label.textContent = isPlaying ? 'Pausar vídeo' : 'Reproducir vídeo';
    };

    const applyMotionPreference = () => {
      if (reducedMotion.matches) {
        video.removeAttribute('autoplay');
        video.pause();
        video.currentTime = 0;
      }
      sync();
    };

    toggle.addEventListener('click', async () => {
      if (video.paused || video.ended) {
        try {
          await video.play();
        } catch (error) {
          console.warn('NUVANX_HOME_VIDEO_PLAY_BLOCKED', error);
        }
      } else {
        video.pause();
      }
      sync();
    });

    video.addEventListener('play', sync);
    video.addEventListener('pause', sync);
    video.addEventListener('ended', sync);

    if (typeof reducedMotion.addEventListener === 'function') {
      reducedMotion.addEventListener('change', applyMotionPreference);
    } else if (typeof reducedMotion.addListener === 'function') {
      reducedMotion.addListener(applyMotionPreference);
    }

    applyMotionPreference();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
