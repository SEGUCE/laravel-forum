<?php

namespace Riari\Forum\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Riari\Forum\Models\Thread;
use Riari\Forum\Models\Traits\HasSlug;

class Category extends BaseModel
{
    use SoftDeletes, HasSlug;

    // Eloquent properties
    protected $table        = 'forum_categories';
    protected $fillable     = ['category_id', 'title', 'subtitle', 'weight', 'allows_threads'];
    public    $timestamps   = false;

    /**
     * Create a new category model instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->perPage = config('forum.preferences.pagination.categories');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function parent()
    {
        return $this->belongsTo('\Riari\Forum\Models\Category', 'category_id')->orderBy('weight');
    }

    public function children()
    {
        return $this->hasMany('\Riari\Forum\Models\Category', 'category_id')->orderBy('weight');
    }

    public function threads()
    {
        return $this->hasMany('\Riari\Forum\Models\Thread');
    }

    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    // Route attributes

    public function getRouteAttribute()
    {
        return $this->getRoute('forum.category.index');
    }

    public function getNewThreadRouteAttribute()
    {
        return $this->getRoute('forum.thread.create');
    }

    // General attributes

    public function getSlugAttribute()
    {
        return Str::slug($this->title);
    }

    public function getThreadsPaginatedAttribute()
    {
        return $this->threads()
            ->orderBy('pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(config('forum.preferences.pagination.threads'));
    }

    public function getPageLinksAttribute()
    {
        return $this->threadsPaginated->render();
    }

    public function getNewestThreadAttribute()
    {
        return $this->threads()->orderBy('created_at', 'desc')->first();
    }

    public function getLatestActiveThreadAttribute()
    {
        return $this->threads()->orderBy('updated_at', 'desc')->first();
    }

    public function getThreadsAllowedAttribute()
    {
        return $this->allows_threads;
    }

    public function getThreadCountAttribute()
    {
        return $this->rememberAttribute('threadCount', function()
        {
            return $this->threads->count();
        });
    }

    public function getPostCountAttribute()
    {
        return $this->rememberAttribute('postCount', function()
        {
            $replyCount = 0;

            $threads = $this->threads()->get(['id']);

            foreach ($threads as $thread) {
                $replyCount += $thread->posts->count() - 1;
            }

            return $replyCount;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Return an array of components used to construct this model's route.
     *
     * @return array
     */
    protected function getRouteComponents()
    {
        $components = [
            'category'  	=> $this->id,
            'categorySlug'  => Str::slug($this->title, '-')
        ];

        return $components;
    }
}