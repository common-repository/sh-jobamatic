(function($){
  $(document).ready(function(){
    
    if($('#jobamatic-advanced-options-trigger').length > 0) {
      $('#jobamatic-advanced-options-trigger').bind('click', function(){
        if($(this).hasClass('collapsed')) {
          $('#jobamatic-advanced-options-wrap').slideDown('slow');
          $(this).html('Hide advanced search options');
          $(this).removeClass('collapsed');
        } else {
          $('#jobamatic-advanced-options-wrap').slideUp('slow');
          $(this).html('Show advanced search options');
          $(this).addClass('collapsed');
        }
      });
    }
    
    $('#jobamatic-search-form').bind('submit', function(){
      var params = new Array();
      
      $.each($(this).serializeArray(), function(i, field){
        if(field.name == 'submit' || field.name == 'reset') return;
        if(field.name == 'q' && field.value == '') {
          // Empty search phrase -- will cause search to use default term(s)
          return;
        }
        params.push(field.name + '=' + encodeURIComponent(field.value));
      });
      
      do_search(params.join('&'));
      return false;
    });
    
    $('#jobamatic-form-reset').bind('click', function(){
      $(':input[name=q]', $(this).parent().parent()).val('');
      $(':input[name=l]', $(this).parent().parent()).val('');
      $(':input[name=m]', $(this).parent().parent()).val('10');
      $(':input[name=ws]', $(this).parent().parent()).val('10');
      $(':input[name=s]', $(this).parent().parent()).val('rd');
      window.location.reload();
    });
    
    do_search();
  });
  
  /*
   * Executes the AJAX job search.
   */
  function do_search(qs) {
  	var url = jobamatic.ajax_url
  	if(qs != null && qs != undefined) {
	    url += '&' + qs;
  	}
  	
  	$('#jobamatic-wrap').html('<div class="loading">Loading ...</div>');
  	
    $.ajax({url: url, action: 'search', success: function(res){
        $('#jobamatic-wrap').html(res);
        // Get the listeners for the pagination.
        $('#jobamatic-pager a.page-numbers').each(function(){
        	$(this).bind('click', function(){
        		var href = $(this).attr('href');
        		var qs = null;
        		if(href.indexOf('?') != -1) {
        			qs = href.substring(href.indexOf('?')+1);
        		}
        		do_search(qs);
        		return false;
        	});
        });
      },
      error: function(){
        $('#jobamatic-wrap').html('<div class="error">There was an error processing your search. Please try again later.</div>');
      }
    });
  }
  
  
})(jQuery);
