<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Category;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserCart;
use App\Observers\BrandObserver;
use App\Observers\CartDetailObserver;
use App\Observers\CartObserver;
use App\Observers\CategoryObserver;
use App\Observers\OrderDetailObserver;
use App\Observers\ProductObserver;
use App\Observers\ShopObserver;
use App\Observers\ShopProductObserver;
use App\Observers\TicketObserver;
use App\Observers\UserCartObserve;
use App\Observers\UserCartObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Category::observe(CategoryObserver::class);
        Shop::observe(ShopObserver::class);
        Product::observe(ProductObserver::class);
        User::observe(UserObserver::class);
        Brand::observe(BrandObserver::class);
        Ticket::observe(TicketObserver::class);
        UserCart::observe(UserCartObserver::class);
        ShopProduct::observe(ShopProductObserver::class);
        Cart::observe(CartObserver::class);
        CartDetail::observe(CartDetailObserver::class);
        OrderDetail::observe(OrderDetailObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
