<?php

namespace Classes;


class BitrixHelper
{
    public static function init()
    {
        $_SERVER["DOCUMENT_ROOT"] = __DIR__ . "/../html";
        require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";
    }


}