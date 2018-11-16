<?php
function feas_default_style($id=0){
$css =<<<end
#feas-$id {
	margin:10px 0px;
}

#feas-searchform-$id {
	background-color:#f7f7f7;
	border:1px solid #e0e0e0;
	padding:5px;
}

#feas-searchform-$id label {
	font-weight:bold;
}

#feas-searchform-$id input,
#feas-searchform-$id select {
	margin-right:5px;
}

#feas-result-$id {
	background-color:#efefff;
	border-top:2px solid #d0d0ff;
	font-size:120%;
	font-weight:bold;
	text-align:right;
	padding:2px;
}
end;

return($css);
}


//define('DEFAULT_STYLE', feas_default_style());
?>