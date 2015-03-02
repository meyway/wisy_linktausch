<!-- Linktausch -->

<script type="text/javascript" defer>

var counter_url = "/files/hh/hamburgaktiv/linktausch/statistik/partnerprogramm.php";
var referrer_type = "";
var referrer_domain = "";
var actions = "do_record"; // do_sort,do_record,do_inform

window.onload = function() {
	if(window.location.hash == "#partner") {
		$.get(counter_url+"?type=partner&referrer="+parseUri(document.referrer).host+"&actions="+actions, function( data ) {
			if(!data.match(/Done./i)) {
				if(typeof console === 'undefined') { ; } else { console.log(data); }
			}
		});
	}
};

</script>

<!-- Ende: Linktausch -->