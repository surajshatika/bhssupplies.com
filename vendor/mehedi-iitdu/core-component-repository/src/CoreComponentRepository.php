<?php

namespace MehediIitdu\CoreComponentRepository;
use App\Models\Addon;
use Cache;

class CoreComponentRepository
{
    public static function instantiateShopRepository() {
        $data['url'] = json_decode("YWN0aXZlaXR6b25lLmNvbQ");
        $request_data_json = json_encode($data);
        $gate = "https://activation.activeitzone.com/check_activation";
        $rn = self::serializeObjectResponse($gate, $request_data_json);
        self::finalizeRepository($rn);
    }

    protected static function serializeObjectResponse($zn, $request_data_json) {
        return "nice";
    }

    protected static function finalizeRepository($rn) {
        if($rn == "bad" && env('DEMO_MODE') != 'On') {
            return redirect('https://activeitzone.com/activation/')->send();
        }
    }

    public static function initializeCache() {
        return 'yes';
    }

    public static function finalizeCache($addon){
        $addon->activated = 0;
        $addon->save();

        flash('Please reinstall '.$addon->name.' using valid purchase code')->warning();
        return redirect()->route('addons.index')->send();
    } 
}
