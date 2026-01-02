<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Models\Personalization;

use App\Models\Traits\HasMetadata;
use App\Models\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $position
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $link_type
 * @property int|null $parent_id
 * @property string $allowed_role
 * @property string|null $icon
 * @property string|null $badge
 * @property string|null $url
 * @property string|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MenuLink> $children
 * @property-read int|null $children_count
 * @property-read MenuLink|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Personalization\Translation> $translations
 * @property-read int|null $translations_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereAllowedRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereBadge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereLinkType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuLink whereUrl($value)
 *
 * @mixin \Eloquent
 */
class MenuLink extends Model
{
    use HasMetadata;
    use Translatable;

    protected $table = 'theme_menu_links';

    private $translatableKeys = [
        'name' => 'text',
        'description' => 'text',
        'badge' => 'text',
        'url' => 'text',
    ];

    protected $fillable = [
        'name',
        'position',
        'type',
        'link_type',
        'parent_id',
        'allowed_role',
        'icon',
        'badge',
        'description',
        'url',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::updated(function ($model) {
            if ($model->parent_id != null) {
                if ($model->parent->link_type != 'dropdown') {
                    $model->parent->update(['link_type' => 'dropdown']);
                }
            }
        });
    }

    public static function newFrontMenu()
    {
        MenuLink::create([
            'name' => 'Home',
            'url' => '/',
            'type' => 'front',
            'position' => '1',
            'icon' => 'bi bi-house-door',
            'link_type' => 'link',
            'allowed_role' => 'all',
        ]);
        MenuLink::create([
            'name' => 'Store',
            'url' => '/store',
            'type' => 'front',
            'position' => '2',
            'icon' => 'bi bi-shop',
            'link_type' => 'link',
            'allowed_role' => 'all',
        ]);
        MenuLink::create([
            'name' => 'Helpdesk',
            'url' => '/client/support',
            'type' => 'front',
            'position' => '3',
            'icon' => 'bi bi-chat-left-text',
            'link_type' => 'link',
            'allowed_role' => 'all',
        ]);
    }

    public static function newBottonMenu()
    {

        $parent = MenuLink::create([
            'name' => 'Bottom Menu',
            'position' => 0,
            'type' => 'bottom',
            'link_type' => 'link',
            'parent_id' => null,
            'badge' => null,
            'allowed_role' => 'all',
            'icon' => 'bi bi-list',
            'url' => '#',
        ]);
        MenuLink::create([
            'name' => 'Condition of use',
            'url' => '/condition-of-use',
            'type' => 'bottom',
            'position' => '1',
            'icon' => 'bi bi-file-earmark-text',
            'link_type' => 'link',
            'allowed_role' => 'all',
            'parent_id' => $parent->id,
        ]);

        MenuLink::create([
            'name' => 'Privacy policy',
            'url' => '/privacy-policy',
            'type' => 'bottom',
            'position' => '2',
            'icon' => 'bi bi-file-earmark-lock',
            'link_type' => 'link',
            'allowed_role' => 'all',
            'parent_id' => $parent->id,
        ]);

        MenuLink::create([
            'name' => 'Status of services',
            'url' => 'https://status.clientxcms.com',
            'type' => 'bottom',
            'position' => '3',
            'icon' => 'bi bi-gear',
            'link_type' => 'new_tab',
            'allowed_role' => 'all',
            'parent_id' => $parent->id,
        ]);
    }

    public function parent()
    {
        return $this->belongsTo(MenuLink::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MenuLink::class, 'parent_id')->orderBy('position');
    }

    public function hasChildren()
    {
        return $this->children->count() > 0;
    }

    public function hasParent()
    {
        return $this->parent_id != null;
    }

    public function canShowed(bool $support)
    {
        if ($this->parent_id != null && ! $support) {
            return false;
        }
        if ($this->allowed_role == 'all') {
            return true;
        }
        if ($this->allowed_role == 'admin' && auth('admin')->check()) {
            return true;
        }
        if ($this->allowed_role == 'logged' && auth('web')->check()) {
            return true;
        }
        if ($this->allowed_role == 'customer' && auth('web')->check() && auth('web')->user()->services()->count() > 0) {
            return true;
        }

        return false;
    }

    public function getShowableChildrens()
    {
        $support = app('theme')->getTheme()->supportOption('menu_dropdown');

        return $this->children->filter(function ($item) use ($support) {
            return $item->canShowed($support);
        });
    }

    public function getHtmlIcon()
    {
        if ($this->icon == null) {
            return '';
        }
        if (filter_var($this->icon, FILTER_VALIDATE_URL)) {
            return '<img src="'.$this->icon.'" alt="'.$this->name.'" class="shrink-0 size-8 mt-1 mr-1">';
        }

        return $this->icon ? '<i class="'.$this->icon.' shrink-0 size-4 mt-1 text-gray-800 dark:text-neutral-200 mr-1"></i>' : '';
    }
}
