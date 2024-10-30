/* 
 * Javascript required for DIY
 */

jQuery.fn.center = function () {
  this.css("position","absolute");
  this.css("top", ( jQuery(window).height() - this.height() ) / 2+jQuery(window).scrollTop() + "px");
  this.css("left", ( jQuery(window).width() - this.width() ) / 2+jQuery(window).scrollLeft() + "px");
  return this;
}

jQuery.fn.enlarge = function () {
  this.css("height", jQuery(document).height() + "px");
  this.css("width", jQuery(document).width() + "px");
  return this;
}

jQuery(document).ready(function() {		
  jQuery("img.instruct-thumbnail-img").click(function(e){

    jQuery("#instruct-thumbnail-bg").css({"opacity" : "0.7"})
            .enlarge()
            .center()
            .fadeIn("slow");			

    jQuery("#instruct-thumbnail-large").html("<img src='"+jQuery(this).attr("large")+"' alt='"+jQuery(this).attr("alt")+"' />")
           .center()
           .fadeIn("slow");			

    return false;
  });

  jQuery(document).keypress(function(e){
    if(e.keyCode==27){
      jQuery("#instruct-thumbnail-bg").fadeOut("slow");
      jQuery("#instruct-thumbnail-large").fadeOut("slow");
    }
  });

  jQuery("#instruct-thumbnail-bg").click(function(){
    jQuery("#instruct-thumbnail-bg").fadeOut("slow");
    jQuery("#instruct-thumbnail-large").fadeOut("slow");
  });

  jQuery("#instruct-thumbnail-large").click(function(){
    jQuery("#instruct-thumbnail-bg").fadeOut("slow");
    jQuery("#instruct-thumbnail-large").fadeOut("slow");
  });

});

//function tbpopup(){
//  jQuery("#background").css({"opacity" : "0.7"})
//            .fadeIn("slow");			
//
//  jQuery("#large").html("<img src='"+$(this).attr("src")+"' alt='"+$(this).attr("alt")+"' /><br/>"+$(this).attr("rel")+"")
//           .center()
//           .fadeIn("slow");			
//
//  return false;
//}

