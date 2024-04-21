<?php

class Meme {
	// 2024-04-21 cmyui: Just found this while deleting dead code.
	//            We gotta keep this -- way too good to delete.

	const PageID = 37;
	const URL = 'Meme';
	const Title = 'Akatsuki - Carroponte';

	public function P() {
		echo('<iframe width="560" height="315" src="https://www.youtube.com/embed/G_QfYsmNIHQ?autoplay=1" frameborder="0" allowfullscreen></iframe><br>');
		for ($i=0; $i < 100; $i++) {
			echo '<h3 class="carroponte" hidden>O-oooooooooo-AAAAE-A-A-I-A-U-JO-oooooooooooo-AAE-O-A-A-U-U-A-E-eee-ee-eee-AAAAE-A-E-I-E-A-JO-ooo-oo-oo-oo-EEEEO-A-AAA-AAAA</h3>';
		}
	}
}
