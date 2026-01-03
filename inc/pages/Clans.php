<?php

class Clans
{
    public const PageID = 140;
    public const URL = 'clans';
    public const Title = 'Akatsuki - Clans Management';
    public const LoggedIn = true;
    public $mh_POST = [];
    public $error_messages = [];

    public function P()
    {
        clir();
        P::AdminClans();
    }

    public function D()
    {
        // This page doesn't handle form submissions
        redirect('index.php?p=140');
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
