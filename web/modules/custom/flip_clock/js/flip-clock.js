(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flip_clock = {
      attach: function (context, settings) {
          // Clock Initiation
          const clock = $('.clock',context).FlipClock(
              {
                  clockFace: 'TwelveHourClock'
              }
          );
      }
  };
})(jQuery, Drupal, drupalSettings);
