(() => {
  'use strict';

  const init = () => {
    const video = document.querySelector('.nvx-home-hero__video');
    const media = video?.closest('.nvx-home-hero__media');

    if (!(video instanceof HTMLVideoElement) || !(media instanceof HTMLElement)) {
      return;
    }

    media.removeAttribute('aria-hidden');
    if (!video.id) {
      video.id = 'nvx-home-hero-video';
    }
    video.setAttribute('aria-label', 'Vídeo de portada NUVANX');

    let toggle = media.querySelector('[data-nvx-home-video-toggle]');
    if (!(toggle instanceof HTMLButtonElement)) {
      toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'nvx-home-hero__motion-toggle';
      toggle.setAttribute('data-nvx-home-video-toggle', '');
      toggle.setAttribute('aria-controls', video.id);
      toggle.innerHTML = '<span data-nvx-home-video-label></span>';
      media.append(toggle);
    }

    const label = toggle.querySelector('[data-nvx-home-video-label]');
    if (!(label instanceof HTMLElement)) {
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
