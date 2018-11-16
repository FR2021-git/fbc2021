// Ajax_filtering 1.0.3

function ajax_filtering_next( manag_no, form_no, class_id )
{
	var cild_id = jQuery( '#ajax_filtering_' + manag_no + '_' + form_no + ' select.' + class_id + ' option:selected' ).val();
	var nest_id = jQuery( '#ajax_filtering_' + manag_no + '_' + form_no + ' select.' + class_id ).attr( 'class' );
	var prev_cild_id = jQuery( '#ajax_filtering_' + manag_no + '_' + form_no + ' select.' + class_id ).prev().val();
	var first_id = jQuery( '#ajax_filtering_' + manag_no + '_' + form_no + ' select:first' ).attr( 'class' );
	nest_id++;
	
	if( ( !( cild_id == prev_cild_id ) ) && ( !( cild_id == '' ) ) )
	{
		if( ( nest_id == first_id ) && ( cild_id == 0 ) )
		{
			jQuery( '#ajax_filtering_' + class_id + ' select' ).remove();
			make_following_elements( manag_no, form_no, cild_id, nest_id );
		
		} else {
			
			jQuery( '#ajax_filtering_' + manag_no + '_' + form_no + ' select.' + class_id ).nextAll().remove();	
			make_following_elements( manag_no, form_no, cild_id, nest_id );
		}
	
	} else {
		
		jQuery( '#ajax_filtering_' + manag_no + '_' + form_no + ' select.' + class_id ).nextAll().remove();
	}
}

function make_following_elements( manag_no, form_no, cild_id, nest_id )
{
	var div_id = jQuery( '#ajax_filtering_' + manag_no + '_' + form_no );
	( manag_no + '_' + form_no ).match( /(\d+)_/ );
	var get_durl = '#feas-searchform-' + RegExp.$1;
	var json_url = jQuery( get_durl ).attr( 'action' ); // initでフックされるURL
	
	var search_element_id = jQuery( '#ajax_filtering_' + manag_no + '_' + form_no ).attr( 'class' );
	json_url = json_url + '/?parent=' + cild_id;
	if( nest_id == null ){ nest_id = 0; }
	json_url = json_url + '&manag_no=' + manag_no + '&form_no=' + form_no;
	div_id.append( '<span class="loading">読み込み...</span>' );
	
	jQuery.getJSON( json_url, function( json )
	{
		if( json )
		{
			var select_form = '<select name="' + search_element_id + '[]" class="' + nest_id + '" onChange="ajax_filtering_next(' + manag_no + ',' + form_no + ',' + nest_id + ')">';
			select_form += '<option value="' + cild_id + '" selected>---未指定---</option>';
			
			jQuery.each( json, function()
			{
				select_form += '<option value="' + this.id + '">' + this.name + '</option>';
			});
			
			select_form += '</select>';
			div_id.children( '.loading' ).remove();
			div_id.append( select_form );
		
		} else {
			
			div_id.children( '.loading' ).remove();
		}
	});
	
	div_id.ajaxComplete( function()
	{
		if( div_id.children().is( '.loading' ) )
		{
			div_id.children( '.loading' ).remove();
			div_id.append( '<span>( 通信エラー )</span>' );
		}
	});
}
