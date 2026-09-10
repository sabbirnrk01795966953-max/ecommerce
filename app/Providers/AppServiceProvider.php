<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $settings = [];
            $navCategories = collect();
            try {
                if (Schema::hasTable('settings')) {
                    $settings = Setting::query()->pluck('value', 'key')->all();
                }
                if (Schema::hasTable('categories') && Schema::hasTable('subcategories')) {
                    $navCategories = Category::query()
                        ->where('is_active', true)
                        ->with(['subcategories' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                        ->orderBy('sort_order')
                        ->get();
                }
            } catch (\Throwable) {
                $settings = [];
                $navCategories = collect();
            }
            $view->with('siteSettings', $settings)->with('navCategories', $navCategories);
        });
    }
}
