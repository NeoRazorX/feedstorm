function fs_close_popups()
{
   $("#b_close_popup").hide();
   $("#popup2url").html('');
   $('div.popup').each(function() {
      $(this).hide();
   });
   $("#shadow_box").fadeOut('fast');
}

function fs_popup_story(url)
{
   $.ajax({
      type: 'POST',
      url: url,
      dataType: 'html',
      data: 'popup=TRUE',
      success: function(datos) {
         $("#shadow_box").fadeIn();
         $("#popups").html("<div id='popup2url' class='popup'>"+datos+"</div>");
         $("#popup2url").css({
            top: $(window).scrollTop()+65,
            left: ($(window).width() - $("#popup2url").outerWidth())/2
         });
         $("#popup2url").show();
         $("#b_close_popup").css({
            top: $(window).scrollTop()+55,
            left: $("#popup2url").position().left - 15,
            display: 'block'
         });
         $('#b_show_comments').click(function () {
            $("#b_show_editions").removeClass('activa');
            $("#b_show_feeds").removeClass('activa');
            $('#b_show_comments').addClass('activa');
            $("#story_editions").hide();
            $("#story_feeds").hide();
            $("#story_comments").show();
         });
         $('#b_show_editions').click(function () {
            $('#b_show_comments').removeClass('activa');
            $('#b_show_feeds').removeClass('activa');
            $("#b_show_editions").addClass('activa');
            $("#story_comments").hide();
            $("#story_feeds").hide();
            $("#story_editions").show();
         });
         $('#b_show_feeds').click(function () {
            $("#b_show_comments").removeClass('activa');
            $("#b_show_editions").removeClass('activa');
            $('#b_show_feeds').addClass('activa');
            $("#story_comments").hide();
            $("#story_editions").hide();
            $("#story_feeds").show();
         });
         $("#new_comment_textarea").click(function() {
            $("#new_comment_controls").show();
         });
      }
   });
}

function fs_popup_edition(url)
{
   $.ajax({
      type: 'POST',
      url: url,
      dataType: 'html',
      data: 'popup=TRUE',
      success: function(datos) {
         $("#shadow_box").fadeIn();
         $("#popups").html("<div id='popup2url' class='popup'>"+datos+"</div>");
         $("#story_feeds").hide();
         $("#popup2url").css({
            top: $(window).scrollTop()+65,
            left: ($(window).width() - $("#popup2url").outerWidth())/2
         });
         $("#popup2url").show();
         $("#b_close_popup").css({
            top: $(window).scrollTop()+55,
            left: $("#popup2url").position().left - 15,
            display: 'block'
         });
         $('#b_show_feeds').click(function () {
            $("#b_show_editions").removeClass('activa');
            $("#story_editions").hide();
            $('#b_show_feeds').addClass('activa');
            $("#story_feeds").show();
            $("#popup2url").css({
               top: $(window).scrollTop()+65,
               left: ($(window).width() - $("#popup2url").outerWidth())/2
            });
         });
         $('#b_show_editions').click(function () {
            $('#b_show_feeds').removeClass('activa');
            $("#story_feeds").hide();
            $("#b_show_editions").addClass('activa');
            $("#story_editions").show();
            $("#popup2url").css({
               top: $(window).scrollTop()+65,
               left: ($(window).width() - $("#popup2url").outerWidth())/2
            });
         });
      }
   });
}

function fs_go2url(url)
{
   window.location.href = url;
}

$(document).ready(function() {
   $("#shadow_box").click(function() {
      fs_close_popups();
   });
   $("#new_comment_textarea").click(function() {
      if( $(this).val() == '¡Escribe algo!' )
      {
         $(this).val('');
      }
      
      $("#new_comment_controls").show();
   });
});