/**
 * General Javascript functions
 * 1.0.0
 */



 var intervalId;

 /**
  * Function that executes a function every second
  * Function called is recomendo_ajax_status_copy
  */
 function setIntervalStatus(){
      intervalId = setInterval( () => {
      recomendo_ajax_status_copy() 
      },1000);
 }
   
 

 /**
  * Function that executes an ajax request in recomendo-plugin.php 
  * to listen for the status of the copy 
  */
 function recomendo_ajax_status_copy(){
    
      var data = {
          'action': 'get_items_progress_background'
      };
      jQuery.post(ajax_object.ajax_url, data, function(data) {
        //console.log(data);
        var obj = JSON.parse(data);

        //check if orders come in the array
        if(obj[0].orders){
            if(obj[0].orders == 100){
              jQuery('#progress_orders_bg').find("code").removeClass('status-okay');
              jQuery('#progress_orders_bg').find("code").addClass('status-good');
            }
            if(obj[0].items == 100){
              jQuery('#progress_item_bg').find("code").removeClass('status-okay');
              jQuery('#progress_item_bg').find("code").addClass('status-good');
            }
          //if true checks if order percent and item percent are completed
          if ((obj[0].items == 100) && (obj[0].orders == 100) )
          {
            //just one more time updates html because if not values keep on 99% and never changes to 100% until user refresh
              jQuery("#progress_item_bg").find("span").html(obj[0].items + '%');
              jQuery("#progress_users_bg").find("span").html(obj[0].users + '%');
              jQuery("#progress_orders_bg").find("span").html(obj[0].orders + '%');   
              clearInterval(intervalId);
              intervalId = null;
          }
          else{
            jQuery("#progress_item_bg").find("span").html(obj[0].items + '%');
            jQuery("#progress_users_bg").find("span").html(obj[0].users + '%');
            jQuery("#progress_orders_bg").find("span").html(obj[0].orders + '%');       
          }
        }else{
          //if no orders in the array , just check for the item progress to be completed
          if (obj[0].items == 100)
          {
            //just one more time updates html because if not values keep on 99% and never changes to 100% until user refresh
              jQuery("#progress_item_bg").find("span").html(obj[0].items + '%');
              jQuery("#progress_users_bg").find("span").html(obj[0].users + '%'); 
              clearInterval(intervalId);
              intervalId = null;
              jQuery('#progress_item_bg').find("code").removeClass('status-okay');
              jQuery('#progress_item_bg').find("code").addClass('status-good');
          }
          else{   
            jQuery("#progress_item_bg").find("span").html(obj[0].items + '%');
            jQuery("#progress_users_bg").find("span").html(obj[0].users + '%');     
          }
      }




      if(obj[0].users == 100){
        jQuery('#progress_users_bg').find("code").removeClass('status-okay');
        jQuery('#progress_users_bg').find("code").addClass('status-good');
      }
    });
 }


function showSyncNotice(){
  jQuery('#forcesync-notice').css('display','unset')
}

function cancel_datasync(){
  jQuery('#forcesync-notice').css('display','none');
}


jQuery(document).ready(function(){
    setIntervalStatus();
  });
