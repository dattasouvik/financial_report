/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.responsive_modal = {
    attach: function (context, settings) {
      // $("#drupal-modal").dialog({height:'auto', width:'auto'});
      // $("#drupal-modal").dialog('widget').trigger('resize.dialogResize');
      // $("#drupal-modal").dialog( "option", { height: 'auto', width: 'auto' } );
    }
  };

})(jQuery, Drupal);
