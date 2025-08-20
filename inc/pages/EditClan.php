<?php

class EditClan {
	const PageID = 141;
	const URL = 'edit-clan';
	const Title = 'Akatsuki - Edit Clan';
	const LoggedIn = true;
	public $mh_POST = [];
	public $error_messages = [];

	public function P() {
		clir();
		P::AdminEditClan();
	}

	public function D() {
		// This page doesn't handle form submissions
		redirect('index.php?p=141');
	}

	public function PrintGetData() {
		return [];
	}

	public function DoGetData() {
		return [];
	}
}
