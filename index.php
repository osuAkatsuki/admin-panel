<?php
// Get functions
require_once './inc/functions.php';
// Frontend stuff
// We're using ob_start to safely send headers while we're processing the script initially.
ob_start();
// Start session with user if we got a valid cookie.
startSessionIfNotStarted();
$c = new RememberCookieHandler();
if ($c->Check()) {
	$v = $c->Validate();
	switch ($v) {
		case ValidateValue::UserBanned:
			addError("You are banned, as such you've been logged out of your account automatically.");
			break;
		// /shrugs
	}
}

// CONTROLLER SYSTEM v2
$model = 'old';
if (isset($_GET['p'])) {
	$found = false;
	foreach ($pages as $page) {
		if (defined(get_class($page).'::PageID') && $page::PageID == $_GET['p']) {
			$found = true;
			$model = $page;
			$title = '<title>'.$page::Title.'</title>';
			$p = $page::PageID;
			if (defined(get_class($page).'::LoggedIn')) {
				if ($page::LoggedIn) {
					clir();
				} else {
					clir(true, 'index.php?p=1&e=1');
				}
			}
			break;
		}
	}
	if (!$found) {
		if (isset($_GET['p']) && !empty($_GET['p'])) {
			$p = $_GET['p'];
		} else {
			$p = 1;
		}
		$title = setTitle($p);
	}
} elseif (isset($_GET['__PAGE__'])) {
	$pages_split = explode('/', $_GET['__PAGE__']);
	if (count($_GET['__PAGE__']) < 2) {
		$title = '<title>Akatsuki</title>';
		$p = 1;
	}
	$found = false;
	foreach ($pages as $page) {
		if ($page::URL == $pages_split[1]) {
			$found = true;
			$model = $page;
			$title = '<title>'.$page::Title.'</title>';
			break;
		}
	}
	if (!$found) {
		$p = 1;
		$title = '<title>Akatsuki</title>';
	}
} else {
	$p = 1;
	$title = '<title>Akatsuki</title>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Dynamic title -->
    <?php echo $title; ?>

    <!-- Bootstrap Core CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap select CSS -->
    <link href="./css/bootstrap-select.min.css" rel="stylesheet">

    <!-- Bootstrap Color Picker CSS -->
    <link href="./css/bootstrap-colorpicker.min.css" rel="stylesheet">

    <!-- Datepicker CSS -->
    <link href="./css/bootstrap-datepicker3.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="./css/style-desktop.css" rel="stylesheet">

	<!-- Style -->
	<link rel="stylesheet" href="https://unpkg.com/alwan/dist/css/alwan.min.css" />

    <!-- Favicon -->
    <link rel="manifest" href="/manifest.json?v=xQQWRwyGed">
    <link rel="mask-icon" href="/safari-pinned-tab.svg?v=xQQWRwyGed" color="#5bbad5">
    <link rel="shortcut icon" href="/favicon.ico?v=xQQWRwyGed">
    <meta name="theme-color" content="#ffffff">

    <meta name=viewport content="width=device-width, initial-scale=1">
	<?php
		if ($isBday && $p == 1) {
			echo '
				<script src="palloncini/palloncini.js"></script>
				<script type="text/javascript">
					particlesJS.load("palloncini", "palloncini/palloncini.conf");
				</script>';
	   	}
   	?>
</head>

<body>
    <!-- Navbar -->
    <?php printNavbar(); ?>

    <!-- Page content (< 100: Normal pages, >= 100: Admin CP pages) -->
    <?php
$status = '';
if ($model !== 'old') {
	P::Messages();
}
if ($p < 100) {
	// Normal page, print normal layout (will fix this in next commit, dw)
	echo '
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">';

                    echo '<div id="content">';
	if ($model === 'old') {
		printPage($p);
	} else {
		echo $status;
		checkMustHave($model);
		$model->P();
	}
	echo '
                    </div>
                </div>
            </div>
        </div>';
       if ($isBday && $p == 1) echo '<div id="palloncini"></div>';
} else {
	// Admin cp page, print admin cp layout
	if ($model === 'old') {
		printPage($p);
	} else {
		echo $status;
		$model->P();
	}
}
?>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-migrate-3.0.0.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="./js/bootstrap.min.js"></script>

    <!-- Bootstrap Select JavaScript -->
    <script src="./js/bootstrap-select.min.js"></script>

    <!-- <!-- Bootstrap Color Picker JavaScript  -->
    <script src="./js/bootstrap-colorpicker.min.js"></script>

	<!-- Datepicker -->
	<script src="./js/bootstrap-datepicker.min.js"></script>

	<script src="https://unpkg.com/alwan/dist/js/alwan.min.js"></script>

    <!-- Custom JavaScript for every page -->
	<script type="text/javascript">
        // Are you sure window
        function sure(redr, pre)
        {
        	var msg = ""
        	if (typeof pre !== "undefined") {
        		msg += pre+"\n";
        	}
        	msg += "Are you sure?";
            var r = confirm(msg);
            if (r == true) window.location.replace(redr);
        }

        function reallysuredialog()
        {
            var r = confirm("This action cannot be undone. Are you sure you want to continue?");
            if (r == true)
                r = confirm("Are you REALLY sure?");
                return r == true;
        }

        function reallysure(redr) {
			reallysuredialog() && window.location.replace(redr)
		}

		function play(id) {
			var audio = $('#audio_'+id)[0];
			if (audio.currentTime <= 0) {
				$.each($('audio'), function () {
					this.pause();
					this.currentTime = 0;
				});
				$.each($("i[id^=icon_]"), function () {
					this.className = "fa fa-play";
				});
				audio.play();
				$('#icon_'+id)[0].className = "fa fa-stop";
			} else {
				audio.pause();
				audio.currentTime = 0;
				$('#icon_'+id)[0].className = "fa fa-play";
			}
		}

		function updateResolution () {
			document.isMobile = window.matchMedia('(max-width: 768px)').matches
		}

		function resetColour() {
			var badgeColour = document.getElementById('badge-colour-value');
			var badgeColourPicker = document.getElementById('badge-colour');

			badgeColour.value = '';
			badgeColour.defaultValue = '';

			badgeColourPicker.value = '';
			badgeColourPicker.defaultValue = '';
			badgeColourPicker.style = 'style="--color: rgba(0, 0, 0, 0);"'
		}

		$(document).ready(function () {
			// Initialize stuff
			$('.colorpicker').colorpicker({format:"hex"});
			$("[data-toggle=popover]").popover();
			$(window).resize(function () {
				updateResolution()
			})
			updateResolution()

			<?php
			if ($p == 109) {
				echo "
					var badgeColour = document.getElementById('badge-colour-value');
					const alwan = new Alwan('#badge-colour', {color: badgeColour.value, format: 'hex'})
					alwan.on('color', (colour) => {
						badgeColour.value = colour.hex;
						badgeColour.defaultValue = colour.hex;
					});
				";
			}
			?>
		})

		$(".getcountry").click(function() {
			var i = $(this);
			$.get("https://ip.zxq.co/" + $(this).data("ip") + "/country", function(data) {
				data = (data === "" ? "dunno" : data);
				i.text("(" + data + ")");
			});
		});
    </script>


    <!-- Custom JavaScript for this page here -->
    <?php
switch ($p) {
	// Admin cp - edit user
	case 103:
		echo '
                <script type="text/javascript">
                    function censorUserpage()
                    {
                        document.getElementsByName("up")[0].value = "[i]:peppy:Userpage reset by an admin.:peppy:[/i]";
                    }

                    function removeSilence()
                    {
                        document.getElementsByName("se")[0].value = 0;
                        document.getElementsByName("sr")[0].value = "";
                    }

					function updatePrivileges(meme = true) {
						var result = 0;
						$("input:checkbox[name=privilege]:checked").each(function(){
							result = Number(result) + Number($(this).val());
						});

						// Remove donor if needed
						var selectValue;
						if (result != '. (Privileges::UserDonor | Privileges::UserNormal | Privileges::UserPublic).') {
							selectValue = result & ~'.Privileges::UserDonor.'
						} else {
							selectValue = result;
						}

						$("#privileges-value").val(result);
						$("#privileges-group").val(selectValue);
						// bootstrap-select is a dank meme
						$("#privileges-group").selectpicker("refresh");
					}

					function groupUpdated() {
						var privileges = $("#privileges-group option:selected").val();
						if (privileges > -1) {
							$("input:checkbox[name=privilege]").each(function(){
								if ( ($(this).val() & privileges) > 0) {
									$(this).prop("checked", true);
								} else {
									$(this).prop("checked", false);
								}
							});
						}
						updatePrivileges();
					}
					function scheduleSaveReminderFadeOut () {
						setTimeout(function () {
							if (document.isMobile) {
								return
							}
							$(".bottom-fixed.enabled>.alert").fadeOut(1000);
						}, 1500)
					}
					$(".unpin").click(function () {
						$(".bottom-fixed").toggleClass("enabled")
						$(".bottom-padded").toggleClass("enabled")
						$(".bottom-fixed>.alert").fadeIn(250)
						var pinned = $(".bottom-fixed").hasClass("enabled")
						if (pinned) {
							scheduleSaveReminderFadeOut()
						}
						window.localStorage.setItem("editUserPinned", pinned.toString())
					})
					$(document).ready(function () {
						if (window.localStorage.getItem("editUserPinned") === null) {
							window.localStorage.setItem("editUserPinned", "true")
						}
						var pinned = window.localStorage.getItem("editUserPinned") === "true"
						if (pinned) {
							$(".bottom-fixed").addClass("enabled");
							$(".bottom-padded").addClass("enabled");
							scheduleSaveReminderFadeOut()
						}
					});
                </script>
                ';
	break;

	case 119:
	echo '
		<script type="text/javascript">
			function updatePrivileges() {
				var result = 0;
				$("input:checkbox[name=privileges]:checked").each(function(){
					result = Number(result) + Number($(this).val());
				});
				$("#privileges-value").attr("value", result);
			}
		</script>
	';
	break;

	case 124:
		$force = (isset($_GET["force"]) && !empty($_GET["force"])) ? '1' : '0';
		echo '<script type="text/javascript">
			var bsid='.htmlspecialchars($_GET["bsid"]).';
			var force='.$force.';
		</script>
		<input id="csrf" type="hidden" value="' . csrfToken() . '">
		<script src="/js/rankbeatmap.js"></script>';
	break;

	case 127:
		echo '<script>
			$(document).ready(function() {
				$("[data-target=#silenceUserModal]").click(function() {
					$("#silenceUserModal").find("input[name=u]").val($(this).data("who"));
					$("#silenceUserModal").find("input[name=c]").val("10");
					$("#silenceUserModal").find("select[name=un]").selectpicker("val", "60");
				});
			});
		</script>';
	break;

	case 134:
		echo "<script>
		$(document).ready(function() {
			$('.datepicker').datepicker({
				orientation: 'bottom',
				format: 'yyyy-mm-dd',
				autoclose: true,
				clearBtn: true
			})
		})
		</script>";
	break;
}
?>

</body>

</html>
<?php
// clear redirpage if we're not on login page
if ($p != 2) {
	unset($_SESSION['redirpage']);
}
ob_end_flush();