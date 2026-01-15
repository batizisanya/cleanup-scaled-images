<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * admin tab default html
 */

?>
    <!-- the content -->
    <div class="content">
      
      <!-- <h2>Cleanup</h2> -->
      <p>Find all scaled images and delete originals related to them.</p>
      <form method="post" id="cleanup-files">
        <p class="submit">
          <input type="submit" name="submit" id="runSearch" class="button button-secondary button-hero" value="Run Dry Search" action="csi_run_search_action" style="margin-right:1em">
          <input type="submit" name="submit" id="runDelete" class="button button-primary button-hero" value="Delete Original Files" action="csi_run_delete_action">
        </p>
      </form>

      <div class="csiMessages"></div>
      <hr>
      <div class="scaledImages" style="height: 450px; overflow: auto;"></div>
      <hr>
      
    </div>
  

  <script>
    jQuery(document).ready(function(){
      jQuery('#cleanup-files .button[type="submit"]').click(function(e){ 
          
          e.preventDefault();
          let buttonAction = jQuery(this).attr("action");
          
          if( // safety fuse 
            buttonAction == "csi_run_delete_action" &&
            ! confirm("Are you sure you want to delete files?")
            ){ return false; }

          const ajaxurl = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
          let nonce = "<?php echo esc_js( wp_create_nonce( 'csi-admin' ) ); ?>";
          let data =  {
            'action': buttonAction,
            'nonce' : nonce
          };

          jQuery.ajax({
              type: "POST",
              url: ajaxurl,
              data: data,
              dataType: 'json',
              cache: false,
              success: function(data)
              {
                  jQuery('.csiMessages').html(data.message);
                  jQuery('.scaledImages').html(data.html);
              }
          });

      }); 
    }); 
  </script>
<?php

