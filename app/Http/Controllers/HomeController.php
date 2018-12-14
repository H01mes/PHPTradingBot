<?php
/**
 * Created by PhpStorm.
 * User: kavehs
 * Date: 12/11/18
 * Time: 22:55
 */

namespace App\Http\Controllers;


use App\Modules;
use App\Order;
use App\Setting;
use App\Signal;
use App\TradeHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        return view('index');
    }

    public function signals()
    {
        return view('signals', [
            'signals' => Signal::orderBy('created_at', 'desc')->paginate(10)
        ]);
    }

    public function system()
    {

        $balances = [];
        $miningHamster = Setting::getValue('miningHamster');

        $binanceConfig = Setting::getValue('binance');
        if ($binanceConfig){
            if (Cache::get('balances')) {
                $balances = json_decode(Cache::get('balances'), true);
            } else {

                $binance = new \Binance\API($binanceConfig['api'], $binanceConfig['secret']);
                if (isset($binanceConfig['proxyEnabled']) && $binanceConfig['proxyEnabled'] != false){
                    $binance->setProxy([
                        'proto' => $binanceConfig['proxy']['proto'],
                        'address' => $binanceConfig['proxy']['host'],
                        'port' => $binanceConfig['proxy']['port'],
                        'username' => $binanceConfig['proxy']['username'],
                        'password' => $binanceConfig['proxy']['password'],
                    ]);
                }
                $balances = null;
                foreach ($binance->balances() as $coin => $balance) {
                    if ($balance['available'] != 0) {
                        $balances[$coin] = $balance;
                    }
                }
                Cache::put('balances', json_encode($balances), Carbon::now()->addMinutes(5));
            }
        }



        $lastPrices = Cache::get('prices');
        $signal = Cache::get('signal');
        return view('system', [
            'lastPrices' => json_decode($lastPrices, true),
            'signal' => json_decode($signal, true),
            'balances' => $balances,
            'binanceConfig' => $binanceConfig,
            'miningHamster' => $miningHamster
        ]);
    }

    public function positions()
    {
        $open = Order::getOpenPositions();
        $prices = Cache::get('prices');
        return view('positions', [
            'open' => $open,
            'prices' => json_decode($prices, true)
        ]);
    }

    public function history()
    {
        $since = Carbon::now()->subDays(30);
        $orders = Order::where('created_at', '>', $since)
            ->where('side','BUY')
            ->whereHas('sellOrder')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $all = $orders;
        return view('history', [
            'all' => $all
        ]);
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function closePosition($id)
    {
        $buyId = Order::find($id);

        $sellOrderInfo = Order::sell($buyId->symbol, $buyId->origQty, $buyId->id);
        return redirect()->back()->with('success', 'position closed.');
    }

    /**
     * @param $id
     * @param Request $request
     * @return void
     */
    public function editPosition($id, Request $request)
    {
        $order = Order::find($id);

        $data = $request->except('_token');

        foreach ($data as $property => $value) {
            $order->{$property} = $value;
        }

        $order->save();
        return redirect()->back()->with('success', 'position modified.');
    }

    public function newPosition($market, $quantity)
    {
        $symbol = TradeHelper::market2symbol($market);
        Order::buy($symbol, $quantity);
        return redirect(route('positions'))->with('success', 'position opened.');
    }

    public function modules()
    {
        return view('modules');
    }

    public function enableModule($moduleId)
    {
        $module = Modules::find($moduleId);
        $module->setActive();
        return redirect()->back();
    }

    public function disableModule($moduleId)
    {
        $module = Modules::find($moduleId);
        $module->setInactive();
        return redirect()->back();
    }

    public function installModule($moduleName)
    {
        Modules::install($moduleName);
        return redirect()->back();
    }

    public function uninstallModule($moduleId)
    {
        $module = Modules::find($moduleId);
        $module->delete();
        return redirect()->back();
    }

    public function saveSettings(Request $request)
    {
        $binance = $request->get('binance');
        $miningHamster = $request->get('miningHamster');


        if ($binance){
            Setting::setValue('binance',$binance);
        }
        if ($miningHamster){
            Setting::setValue('miningHamster',$miningHamster);
        }


        return redirect()->back()->with('success');
    }

}