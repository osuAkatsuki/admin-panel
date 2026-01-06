<?php

class SharedDevices {
	const PageID = 142;
	const URL = 'shared-devices';
	const Title = 'Akatsuki - Shared Devices';
	const LoggedIn = true;
	public $mh_POST = [];
	public $error_messages = [];

	public function P() {
		sessionCheckAdmin(Privileges::AdminManageUsers);
		P::AdminSharedDevices();
	}

	public function D() {
		// This page doesn't handle form submissions directly
		// Actions are handled through submit.php
		redirect('index.php?p=142');
	}

	public function PrintGetData() {
		return [];
	}

	public function DoGetData() {
		return [];
	}
}
