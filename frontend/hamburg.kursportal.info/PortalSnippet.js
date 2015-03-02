<!-- Linktausch -->

<script type="text/javascript">

var counter_url = "/files/hh/linktausch/statistik/partnerprogramm.php";
var referrer_type = "";                
var referrer_domain = document.referrer.match(/:\\/\\/(.[^/]+)/)[1].replace(/^www./i, "");
var actions = "do_record"; // do_sort,do_record,do_inform

window.onload = function() {
	if(window.location.hash == "#partner") {

$.get(counter_url+"?type=partner&referrer="+referrer_domain+"&actions="+actions, function(data) {
			if(!data.match(/Done./i)) {
				if(typeof console === 'undefined') { ; } else { console.log(data); }
			}
		});
	} else {
      }
};


</script>

<!-- Ende: Linktausch -->