<?php

class Privileges
{
    public const UserPublic = 1 << 0;
    public const UserNormal = 1 << 1;
    public const UserDonor = 1 << 2;
    public const AdminAccessRAP = 1 << 3;
    public const AdminManageUsers = 1 << 4;
    public const AdminBanUsers = 1 << 5;
    public const AdminSilenceUsers = 1 << 6;
    public const AdminWipeUsers = 1 << 7;
    public const AdminManageBeatmaps = 1 << 8;
    public const AdminManageServers = 1 << 9;
    public const AdminManageSettings = 1 << 10;
    public const AdminManageBetaKeys = 1 << 11;
    public const AdminManageReports = 1 << 12;
    public const AdminManageDocs = 1 << 13;
    public const AdminManageBadges = 1 << 14;
    public const AdminViewRAPLogs = 1 << 15;
    public const AdminManagePrivileges = 1 << 16;
    public const AdminSendAlerts = 1 << 17;
    public const AdminChatMod = 1 << 18;
    public const AdminKickUsers = 1 << 19;
    public const UserPendingVerification = 1 << 20;
    public const UserTournamentStaff = 1 << 21;
    public const AdminCaker = 1 << 22;
    public const UserPremium = 1 << 23;
    public const AdminFreezeUsers = 1 << 24;
    public const AdminManageNominators = 1 << 25;
}
