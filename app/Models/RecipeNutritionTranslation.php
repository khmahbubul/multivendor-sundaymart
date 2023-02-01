<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\RecipeNutritionTranslation
 *
 * @property int $id
 * @property int $recipe_nutrition_id
 * @property string $title
 * @property string $locale
 * @method static Builder|RecipeNutritionTranslation actualTranslation($lang)
 * @method static Builder|RecipeNutritionTranslation newModelQuery()
 * @method static Builder|RecipeNutritionTranslation newQuery()
 * @method static Builder|RecipeNutritionTranslation query()
 * @method static Builder|RecipeNutritionTranslation whereId($value)
 * @method static Builder|RecipeNutritionTranslation whereLocale($value)
 * @method static Builder|RecipeNutritionTranslation whereRecipeNutritionId($value)
 * @method static Builder|RecipeNutritionTranslation whereTitle($value)
 * @mixin Eloquent
 */
class RecipeNutritionTranslation extends Model
{

    use HasFactory;
    protected $fillable = ['title', 'locale'];
    public $timestamps = false;

    public function scopeActualTranslation($query, $lang)
    {
        $lang = $lang ?? config('app.locale');
        return self::where('locale', $lang)->first() ? $query->where('locale', $lang) : $query->first();
    }
}
