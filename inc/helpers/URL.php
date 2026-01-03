<?php

class URL
{
    public static function PublicAvatarServiceBaseUrl()
    {
        global $PUBLIC_AVATARS_SERVICE_BASE_URL;

        return isset($PUBLIC_AVATARS_SERVICE_BASE_URL) ? $PUBLIC_AVATARS_SERVICE_BASE_URL : 'https://a.akatsuki.gg';
    }
}
