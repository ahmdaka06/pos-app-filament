<?php

namespace App\Observers;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Cache;

class StoreSettingObserver
{
    public function saved(StoreSetting $storeSetting): void
    {
        Cache::forget(StoreSetting::CACHE_KEY);
    }

    public function deleted(StoreSetting $storeSetting): void
    {
        Cache::forget(StoreSetting::CACHE_KEY);
    }
}
