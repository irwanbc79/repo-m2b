<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Helper untuk mengelola cache invalidation
 * digunakan untuk memastikan data selalu fresh setelah update/create
 */
class CacheHelper
{
    /**
     * Invalidate customer dashboard cache
     * Panggil ini setelah ada perubahan shipment/invoice customer
     */
    public static function invalidateCustomerDashboard(int $customerId): void
    {
        $versionKey = 'customer_dashboard_version_' . $customerId;
        $currentVersion = Cache::get($versionKey, 1);
        Cache::put($versionKey, $currentVersion + 1, now()->addDay());
        
        // Also clear the main cache key
        Cache::forget('customer_dashboard_' . $customerId . '_v' . $currentVersion);
    }
    
    /**
     * Invalidate all tax rate caches
     * Panggil ini setelah kurs pajak diupdate
     */
    public static function invalidateTaxRates(): void
    {
        $currencies = ['USD', 'AUD', 'CAD', 'DKK', 'HKD', 'MYR', 'NZD', 'NOK', 'GBP', 'SGD', 
                      'SEK', 'CHF', 'JPY', 'CNY', 'EUR', 'KRW', 'SAR', 'THB', 'BND', 'INR'];
        
        $today = now()->format('Y-m-d');
        
        foreach ($currencies as $currency) {
            Cache::forget('tax_rate_' . $currency . '_' . $today);
        }
        
        // Also clear general caches
        Cache::forget('kurs_pajak_full_page');
        Cache::forget('kurs_pajak_customer_active_' . $today);
        Cache::forget('admin_usd_rate');
        Cache::forget('customer_usd_rate_header');
    }
    
    /**
     * Invalidate admin dashboard/statistics cache
     */
    public static function invalidateAdminStats(): void
    {
        $versionKey = 'admin_stats_version';
        $currentVersion = Cache::get($versionKey, 1);
        Cache::put($versionKey, $currentVersion + 1, now()->addDay());
        Cache::forget('shipment_stats');
    }
    
    /**
     * Clear all application caches (use with caution)
     */
    public static function clearAll(): void
    {
        Cache::flush();
    }
    
    /**
     * Get cache key with version
     */
    public static function key(string $baseKey): string
    {
        $version = Cache::get($baseKey . '_version', 1);
        return $baseKey . '_v' . $version;
    }
    
    /**
     * Increment cache version (effectively invalidates cache)
     */
    public static function incrementVersion(string $baseKey): void
    {
        $versionKey = $baseKey . '_version';
        $currentVersion = Cache::get($versionKey, 1);
        Cache::put($versionKey, $currentVersion + 1, now()->addDay());
    }
}
