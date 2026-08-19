<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Concerns\SerializesDatesInAppTimezone;

class QuickPhrase extends Model
{
    use SerializesDatesInAppTimezone;
    use SoftDeletes;

    protected $fillable = [
        'shortcut', 'phrase', 'category', 'scope', 'is_active', 'user_id', '_who_added'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function addedBy()
    {
        return $this->belongsTo('App\User', '_who_added');
    }

    /**
     * Scope for active phrases
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for system phrases
     */
    public function scopeSystem($query)
    {
        return $query->where('scope', 'system');
    }

    /**
     * Scope for personal phrases belonging to a specific user
     */
    public function scopePersonal($query, $userId)
    {
        return $query->where('scope', 'personal')->where('user_id', $userId);
    }

    /**
     * Get phrases available to a user (system + personal)
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('scope', 'system')
                ->orWhere(function ($q2) use ($userId) {
                    $q2->where('scope', 'personal')->where('user_id', $userId);
                });
        });
    }

    /**
     * Scope for phrases by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * 病历页侧栏用：按分类分组的可用短语。
     *
     * 侧栏原来是 Blade 里写死的 8 条（走语言键），而这张表里有 30 条、分三类 ——
     * 诊所在「快捷短语」页加的短语在病历页根本看不到。同类产品的短语面板也是
     * 按科室/分类分组的（牙体牙髓、正畸、儿牙、外科、修复、牙周）。
     *
     * category 存的是英文 slug（examination / diagnosis / treatment），
     * 中文标签走语言文件；quick_phrase_categories 表用的是中文 name 且没有外键
     * 关联到这里，所以不参与分组。
     */
    public static function groupedForUser(int $userId): \Illuminate\Support\Collection
    {
        return self::active()
            ->forUser($userId)
            ->orderBy('category')
            ->orderBy('id')
            ->get(['id', 'shortcut', 'phrase', 'category'])
            ->groupBy('category');
    }

    /**
     * Search phrases by shortcut or phrase content
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('shortcut', 'like', "%{$term}%")
                ->orWhere('phrase', 'like', "%{$term}%");
        });
    }
}
