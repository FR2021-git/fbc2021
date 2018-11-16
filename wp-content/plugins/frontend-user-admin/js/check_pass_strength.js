(function($){

	function check_pass_strength () {

		var pass = $('#pass1').val();
		var user = $('#user_login').val();

		$('#pass-strength-result').removeClass('short bad good strong');
		if ( ! pass ) {
			$('#pass-strength-result').html( pwsL10n.empty );
			return;
		}

		var strength = passwordStrength(pass, user);

		if ( 2 == strength )
			$('#pass-strength-result').addClass('bad').html( pwsL10n.bad );
		else if ( 3 == strength )
			$('#pass-strength-result').addClass('good').html( pwsL10n.good );
		else if ( 4 == strength )
			$('#pass-strength-result').addClass('strong').html( pwsL10n.strong );
		else
			// this catches 'Too short' and the off chance anything else comes along
			$('#pass-strength-result').addClass('short').html( pwsL10n.short );

	}

	function update_nickname () {

		var nickname = $('#nickname').val();
		var display_nickname = $('#display_nickname').val();

		if ( nickname == '' ) {
			$('#display_nickname').remove();
		}
		$('#display_nickname').val(nickname).html(nickname);

	}

	$(document).ready( function() {
		$('#nickname').blur(update_nickname);
		$('#pass1').val('').keyup( check_pass_strength );
		$('.color-palette').click(function(){$(this).siblings('input[name=admin_color]').attr('checked', 'checked')});
    });
})(jQuery);