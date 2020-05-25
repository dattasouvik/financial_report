(function ($, Drupal, drupalSettings) {
    'use strict';
    Drupal.behaviors.deposit_form = {
        attach: function (context, settings) {
            // Handling Radio Values
            $("[name = 'deposit_select']", context).click(
                function () {
                    $(".deposit_selected_type").val(this.value);
                    // Remove Bank Name on deselction
                    if(this.value === "post"){
                      $("[name = 'bank_neme']").val('');
                    }
                }
            );

            // Form Reset
            $("#reset_buuton").click(function(){
              this.form.reset();
            });

            let autocomplete = Drupal.autocomplete;
            let postOfficeSchemeName = jQuery(
                "[name='field_post_office_deposit']"
            ).autocomplete(
                {
                    select: function (event, ui) {
                        let post_title = ui.item.value;
                        let post_title_array;
                        post_title_array = post_title.split("(");
                        if (typeof(post_title_array[1]) != "undefined"
                            && post_title_array[1] !== null
                        ) {
                            jQuery("[name='field_post_office_deposit']").val(
                                post_title_array[0].replace(")", "")
                            );
                        }
                        return false;
                    }
                }
            );
            let bankSchemeName = jQuery("[name='field_bank_deposit']")
            .autocomplete(
                {
                    select: function (event, ui) {
                        let bank_title = ui.item.value;
                        let bank_title_array;
                        bank_title_array = bank_title.split("(");
                        if (typeof(bank_title_array[1]) != "undefined"
                            && bank_title_array[1] !== null
                        ) {
                            jQuery("[name='field_bank_deposit']").val(
                                bank_title_array[0].replace(")", "")
                            );
                        }
                        return false;
                    }
                }
            );
        }
    };
})(jQuery, Drupal, drupalSettings);
