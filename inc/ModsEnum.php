<?php

// All the mods' values in any mods combination integer.
// Usage: $modInt & ModsEnum::ModToSeeIfIsInTheModsCombination
// If you don't really know what are these, lookup "bitwise operators php" on google.
class ModsEnum
{
    public const None = 0;
    public const NoFail = 1;
    public const Easy = 2;
    public const NoVideo = 4;
    public const Hidden = 8;
    public const HardRock = 16;
    public const SuddenDeath = 32;
    public const DoubleTime = 64;
    public const Relax = 128;
    public const HalfTime = 256;
    public const Nightcore = 512;
    public const Flashlight = 1024;
    public const Autoplay = 2048;
    public const SpunOut = 4096;
    public const Relax2 = 8192;
    public const Perfect = 16384;
    public const Key4 = 32768;
    public const Key5 = 65536;
    public const Key6 = 131072;
    public const Key7 = 262144;
    public const Key8 = 524288;
    public const keyMod = 1015808;
    public const FadeIn = 1048576;
    public const Random = 2097152;
    public const LastMod = 4194304;
    public const Key9 = 16777216;
    public const Key10 = 33554432;
    public const Key1 = 67108864;
    public const Key3 = 134217728;
    public const Key2 = 268435456;
}
