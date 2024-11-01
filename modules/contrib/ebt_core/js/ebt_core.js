(function ($, Drupal) {

  /**
   * EBT Core behavior.
   */
  Drupal.behaviors.ebtCore = {
    attach: function (context, settings) {
      $.each(drupalSettings['ebtCore'], function(block_class, value) {
        if (value['ebtCoreParallax'] != undefined) {
          $('.' + block_class).parallax({
            imageSrc: Drupal.checkPlain(value['ebtCoreParallax']['mediaUrl'])
          });
        }

        if (value['ebtCoreBackgroundRemoteVideo'] != undefined) {
          if (value['ebtCoreBackgroundRemoteVideo']['mediaProvider'] == 'YouTube') {
            const $elements = $(once('youtube-video', '.' + block_class + ' .bg-inner', context));
            let options = {
              videoURL: Drupal.checkPlain(value['ebtCoreBackgroundRemoteVideo']['mediaUrl']),
              containment: '.' + block_class,
              autoPlay: value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['autoPlay'],
              showControls: value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['showControls'],
              mute: value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['mute'],
              startAt: value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['startAt'],
              opacity: value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['opacity'],
              addRaster: value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['addOverlay'],
              useOnMobile: value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['useOnMobile'],
              playOnlyIfVisible: value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['playOnlyIfVisible'],
              stopMovieOnBlur: value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['stopMovieOnBlur'],
              loop: value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['loop'],
              quality: 'default',
            }
            if (value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['coverImage'].length > 0) {
              options.coverImage = value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['coverImage'];
            }
            if (value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['mobileFallbackImage'].length > 0) {
              options.coverImage = value['ebtCoreBackgroundRemoteVideo']['background_video_settings']['mobileFallbackImage'];
            }
            $elements.YTPlayer(options);
          }
        }

        if (value['ebtCoreBackgroundVideo'] != undefined) {
          const $elements = $(once('local-video', '.' + block_class, context));
          if ($elements.length) {
            let options = {
              poster: null,
              overlay: false,
              overlayColor: value['ebtCoreBackgroundVideo']['background_video_settings']['overlayColor'],
              overlayAlpha: value['ebtCoreBackgroundVideo']['background_video_settings']['overlayAlpha'],
            }

            let attributes = {
              autoplay: value['ebtCoreBackgroundVideo']['background_video_settings']['autoPlay'],
              controls: value['ebtCoreBackgroundVideo']['background_video_settings']['showControls'],
              loop: value['ebtCoreBackgroundVideo']['background_video_settings']['loop'],
              muted: value['ebtCoreBackgroundVideo']['background_video_settings']['mute'],
              playsInline: true,
            };

            if (value['ebtCoreBackgroundVideo']['background_video_settings']['addOverlay']) {
              options.overlay = true;
            }

            if (value['ebtCoreBackgroundVideo']['background_video_settings']['coverImage'].length > 0) {
              options.poster = value['ebtCoreBackgroundVideo']['background_video_settings']['coverImage'];
            }

            if (value['ebtCoreBackgroundVideo']['mediaUrl'].endsWith('.webm')) {
              options.webm = value['ebtCoreBackgroundVideo']['mediaUrl'];
              var instance = new vidbg('.' + block_class, options, attributes);
            }
            else if (value['ebtCoreBackgroundVideo']['mediaUrl'].endsWith('.mp4')) {
              options.mp4 = value['ebtCoreBackgroundVideo']['mediaUrl'];
              var instance = new vidbg('.' + block_class, options, attributes);
            }
            else {
              console.log('Background video file must be mp4 or webm');
            }
          }

        }
      });
    }
  };

})(jQuery, Drupal);
