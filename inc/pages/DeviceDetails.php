<?php

class DeviceDetails {
	const PageID = 143;
	const URL = 'device-details';
	const Title = 'Akatsuki - Device Details';
	public $mh_POST = [];
	public $error_messages = [];

	public function P() {
		sessionCheckAdmin(Privileges::AdminManageUsers);
		P::AdminDeviceDetails();
	}
}
