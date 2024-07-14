<?php

class Privileges {
	const UserPublic				= 1 << 0;
	const UserNormal				= 1 << 1;
	const UserDonor					= 1 << 2;
	const AdminAccessRAP			= 1 << 3;
	const AdminManageUsers			= 1 << 4;
	const AdminBanUsers				= 1 << 5;
	const AdminSilenceUsers			= 1 << 6;
	const AdminWipeUsers			= 1 << 7;
	const AdminManageBeatmaps		= 1 << 8;
	const AdminManageServers		= 1 << 9;
	const AdminManageSettings		= 1 << 10;
	const AdminManageBetaKeys		= 1 << 11;
	const AdminManageReports		= 1 << 12;
	const AdminManageDocs			= 1 << 13;
	const AdminManageBadges			= 1 << 14;
	const AdminViewRAPLogs			= 1 << 15;
	const AdminManagePrivileges		= 1 << 16;
	const AdminSendAlerts			= 1 << 17;
	const AdminChatMod				= 1 << 18;
	const AdminKickUsers			= 1 << 19;
	const UserPendingVerification	= 1 << 20;
	const UserTournamentStaff		= 1 << 21;
	const AdminCaker				= 1 << 22;
	const UserPremium				= 1 << 23;
	const AdminFreezeUsers			= 1 << 24;
	const AdminManageNominators		= 1 << 25;
}
