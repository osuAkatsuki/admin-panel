<?php

class EditClan
{
    public const PageID = 141;
    public const URL = 'edit-clan';
    public const Title = 'Akatsuki - Edit Clan';
    public const LoggedIn = true;
    public $mh_POST = [];
    public $error_messages = [];

    public function P()
    {
        clir();
        P::AdminEditClan();
    }

    public function D()
    {
        // This page doesn't handle form submissions
        redirect('index.php?p=141');
    }

    public function PrintGetData()
    {
        return [];
    }

    public function DoGetData()
    {
        return [];
    }
}
