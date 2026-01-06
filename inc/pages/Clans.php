<?php

class Clans {
	const PageID = 140;
	const URL = 'clans';
	const Title = 'Akatsuki - Clans Management';
	const LoggedIn = true;
	public $mh_POST = [];
	public $error_messages = [];

	public function P() {
		sessionCheckAdmin(Privileges::AdminManageUsers);
		P::AdminClans();
	}

	public function D() {
		// This page doesn't handle form submissions
		redirect('index.php?p=140');
	}

	public function PrintGetData() {
		return [];
	}

	public function DoGetData() {
		return [];
	}
}
