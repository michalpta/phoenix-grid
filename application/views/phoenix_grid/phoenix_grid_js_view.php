// global data variable adjusted and sent on each ajax request
var phoenix_grid_data={}

function load_crud() {	

	// no form submit on enter
    $(window).keydown(function (e) {
		if (e.keyCode == 13) {
			e.preventDefault();
		}
    });
	
	// searchbox hide after clicking outside
	$(document).click(function (e)
	{
		var container = $('.phoenix_grid_option_box');
		if (container.has(e.target).length === 0)
		{
			container.fadeOut();
			container.find('.phoenix_grid_filterbox').fadeOut();
		}
	});

    // individual setup of each grid
    $(".phoenix_grid").each(function(index,value) {

		// COLUMN OPTION BOX: column header click event - opens column option box
		$(value).on('click','.phoenix_grid_column_header',function(e) {
			e.stopPropagation();
			p=$(this).parent().position();
			
			$(value).find('.phoenix_grid_filterbox').empty();
			filterbox=$(value).find('.phoenix_grid_option_box');
			filterbox.hide();
			
            var absoluteLeft   = p.left;
			var absoluteTop    = p.top;
			var absoluteRight  = absoluteLeft + filterbox.outerWidth();
			var absoluteBottom = absoluteTop + filterbox.outerHeight();

			var viewportRight  = $(window).width()  + $(window).scrollLeft(); // scroll left will take into account the position of the horizontal scrollbar
			var viewportBottom = $(window).height() + $(window).scrollTop();  // scroll top will take into account the position of the vertical scrollbar

			if (absoluteRight > viewportRight) {
				p.left=p.left-(absoluteRight-viewportRight+5);	
			}
			if (absoluteBottom > viewportBottom) {

			}
			p.top=p.top+30;
			filterbox.css('left',p.left);
			filterbox.css('top',p.top);
			filterbox.fadeIn();
			$(value).find('.phoenix_grid_searchbox').val('');
			$(value).find('.phoenix_grid_searchbox').focus();
            phoenix_grid_data['phoenix_grid_id']=$(value).attr('id');
			phoenix_grid_data[$(value).attr('id')+'_col_id']=$(this).attr('id');
			$.ajax({
				type: "POST",
				url: "<?=site_url('phoenix_grid_controller/option_box')?>",
				data: phoenix_grid_data
			}).done(function(result) {
					$(value).find('.phoenix_grid_filterbox').html(result);
					$(value).find('.phoenix_grid_filterbox').show('scale');
			});
		});
		
		// filtering after clicking on filter button
		$(value).find('.phoenix_grid_filterbutton').click(function(e) {
			$(value).find('.phoenix_grid_option_box').fadeOut();
			$(value).children('.phoenix_grid_page').html("loading...");
			e.stopPropagation();
            id=$(value).attr('id');
            phoenix_grid_data['phoenix_grid_id']=id;
            phoenix_grid_data['phoenix_grid_filters']={};
            phoenix_grid_data['phoenix_grid_searches']={};
            var selectedItems = [];
            $(value).find('.phoenix_grid_filterbox input[type="checkbox"]:checked').each(function () {
                    selectedItems.push($(this).val());
            });
            phoenix_grid_data['phoenix_grid_filters'][phoenix_grid_data[id+'_col_id']]=selectedItems;
            phoenix_grid_data['phoenix_grid_searches'][phoenix_grid_data[id+'_col_id']]=$(value).find('.phoenix_grid_searchbox').val();
			$.ajax({
				type: "POST",
				url: "<?=site_url('phoenix_grid_controller/apply_filter')?>",
				data: phoenix_grid_data
			}).done(function(result) {
				insert_grid(result,value);
			});
		});
		
		// filtering after hitting enter button within the searchbox
		$(value).find('.phoenix_grid_searchbox').keyup(function (e) {
			if (e.keyCode == 13) {
				$(value).find('.phoenix_grid_filterbutton').trigger('click');
			}
		});
		
		// clearing filter on a column
		$(value).find('.phoenix_grid_filterclear').click(function(e) {
			$(value).find('.phoenix_grid_option_box').fadeOut();
			$(value).children('.phoenix_grid_page').html("loading...");
			e.stopPropagation();
			e.preventDefault();
            phoenix_grid_data['phoenix_grid_id']=$(value).attr('id');
			$.ajax({
				type: "POST",
				url: "<?=site_url('phoenix_grid_controller/remove_filter')?>",
				data: phoenix_grid_data
			}).done(function(result) {
				insert_grid(result,value);
			});
		});
		
		// switching pages
		$(value).on('click','.phoenix_grid_page_button',function(e) {
			e.stopPropagation();
			e.preventDefault();
			$(value).children('.phoenix_grid_page').html("loading...");
			phoenix_grid_data['phoenix_grid_id']=$(value).attr('id');
			phoenix_grid_data['page_number']=$(this).attr('id');
			$.ajax({
				type: "POST",
				url: "<?=site_url('phoenix_grid_controller/change_page')?>",
				data: phoenix_grid_data
			}).done(function(result) {
					insert_grid(result,value);
			});
		});
		
		// reseting the grid
		$(value).on('click','.phoenix_grid_reset_button',function(e) {
			e.stopPropagation();
			e.preventDefault();
			$(value).find('.phoenix_grid_page').html("loading...");
            phoenix_grid_data['phoenix_grid_id']=$(value).attr('id');
			$.ajax({
				type: "POST",
				url: "<?=site_url('phoenix_grid_controller/reset')?>",
				data: phoenix_grid_data
			}).done(function(result) {
				insert_grid(result,value);
			});
		});
		
		// first load of the grid
		phoenix_grid_data['phoenix_grid_id']=$(value).attr('id');
		$.ajax({
            type: "POST",
            url: "<?=site_url('phoenix_grid_controller')?>",
            data: phoenix_grid_data
		}).done(function(result) {
				insert_grid(result,value);
		});
		
	});
}

$(document).ready(function() {
    load_crud();
});

function insert_grid(result,value) {
    $(value).children('.phoenix_grid_page').hide()
    $(value).children('.phoenix_grid_page').html(result);
    $(value).children('.phoenix_grid_page').fadeIn();
    $(value).find('.phoenix_grid_column_header').css('cursor','pointer');
}