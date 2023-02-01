<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\ShopBrand;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Support\Str;

class ShopProductObserver
{
    private ShopBrand $shopBrand;
    private ShopCategory $shopCategory;
    private Category $category;

    public function __construct(ShopBrand $shopBrand,ShopCategory $shopCategory,Category $category)
    {
        $this->shopBrand = $shopBrand;
        $this->shopCategory = $shopCategory;
        $this->category = $category;
    }

    public function creating(ShopProduct $shopProduct)
    {
        $shopProduct->uuid = Str::uuid();
    }
    /**
     * Handle the ShopProduct "created" event.
     *
     * @param ShopProduct $shopProduct
     * @return void
     */
    public function created(ShopProduct $shopProduct)
    {
        $shop_id = $shopProduct->shop_id;

        $this->setShopBrand($shopProduct,$shop_id);

        $this->setShopCategory($shopProduct,$shop_id);
    }

    /**
     * Handle the ShopProduct "updated" event.
     *
     * @param ShopProduct $shopProduct
     * @return void
     */
    public function updated(ShopProduct $shopProduct)
    {
        $shop_id = $shopProduct->shop_id;

        $this->setShopBrand($shopProduct,$shop_id);

        $this->setShopCategory($shopProduct,$shop_id);

    }

    /**
     * Handle the ShopProduct "deleted" event.
     *
     * @param ShopProduct $shopProduct
     * @return void
     */
    public function deleted(ShopProduct $shopProduct)
    {
        //
    }

    /**
     * Handle the ShopProduct "restored" event.
     *
     * @param ShopProduct $shopProduct
     * @return void
     */
    public function restored(ShopProduct $shopProduct)
    {
        //
    }

    /**
     * Handle the ShopProduct "force deleted" event.
     *
     * @param ShopProduct $shopProduct
     * @return void
     */
    public function forceDeleted(ShopProduct $shopProduct)
    {
        //
    }

    public function setShopBrand($shopProduct,int $shop_id)
    {
        /** @var ShopProduct $shopProduct */
        $brand_id = $shopProduct->product ? $shopProduct->product->brand_id : null;
        $shopBrand = $this->shopBrand
            ->where('shop_id', $shop_id)
            ->where('brand_id', $brand_id)
            ->first();

        if (!$shopBrand) {
            $this->shopBrand->create(['shop_id' => $shop_id, 'brand_id' => $brand_id]);
        }
    }

    public function setShopCategory($shopProduct,int $shop_id)
    {
        /** @var ShopProduct $shopProduct */

        $category_id = $shopProduct->product ? $shopProduct->product->category_id : null;
        $category = $this->category->where('id',$category_id)->first();
//        $parent_category_id = $category->parent ? $category->parent->id : $category_id;
        if ($category)
        {
            $shopCategory = $this->shopCategory
                ->where('shop_id', $shop_id)
                ->where('category_id', $category->id)
                ->first();
            if (!$shopCategory) {
                $this->shopCategory->create(['shop_id' => $shop_id, 'category_id' => $category->id]);
            }
        }
    }
}
