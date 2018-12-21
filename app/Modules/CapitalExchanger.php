<?php
/**
 * Created by PhpStorm.
 * User: kavehs
 * Date: 12/15/18
 * Time: 14:01
 */

namespace App\Modules;


use App\Modules;

class CapitalExchanger extends Modules
{
    public static $description = 'prepares the required asset to proceed with order';

    public function menus()
    {
        return [
            [
                'route' => 'capitalExchanger',
                'text' => 'Capital Mover',
                'module' => 'CapitalExchanger'
            ]
        ];
    }

    public function CapitalExchangerPage()
    {
        view()->addNamespace('capitalExchanger', app_path('Modules/capitalExchanger/view'));
        return view('capitalExchanger::layout');
    }
}