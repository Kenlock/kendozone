<?php

namespace App;

use Carbon\Carbon;
use Countries;
use Cviebrock\EloquentSluggable\SluggableInterface;
use Cviebrock\EloquentSluggable\SluggableTrait;
use GeoIP;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\AuditingTrait;


/**
 * @property mixed type
 * @property float latitude
 * @property float longitude
 * @property mixed created_at
 * @property mixed updated_at
 * @property mixed deleted_at
 */
class Tournament extends Model implements SluggableInterface
{
    use SoftDeletes;
    use SluggableTrait;
    use AuditingTrait;

    protected $sluggable = [
        'build_from' => 'name',
        'save_to' => 'slug',
    ];
    protected $table = 'tournament';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'dateIni','dateFin', 'registerDateLimit',
        'sport', 'cost',
        'venue', 'latitude', 'longitude',
        'type', 'level_id',
        'promoter','host_organization','technical_assistance'


    ];

    protected $dates = ['date', 'registerDateLimit', 'created_at', 'updated_at', 'deleted_at'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($tournament) {

            $tournament->addGeoData();

        });
        static::deleting(function ($tournament) {
            foreach ($tournament->categoryTournaments as $ct) {
                $ct->delete();
            }
            $tournament->invites()->delete();

        });
        static::restoring(function ($tournament) {

            foreach ($tournament->categoryTournaments()->withTrashed()->get() as $ct) {
                $ct->restore();
            }

        });

    }
    function addGeoData()
    {
        $location = GeoIP::getLocation(getIP()); // Simulating IP in Mexico DF
        $country = Countries::where('name', '=', $location['country'])->first();
        if (is_null($country)) {
            $latitude = 48.858222;
            $longitude = 2.2945;
        } else {
            $latitude = $location['lat'];
            $longitude = $location['lon'];
        }
        $this->latitude = $latitude;
        $this->longitude = $longitude;


    }
    /**
     * A tournament is owned by a user
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    /**
     * Get All Tournaments levels
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function level()
    {
        return $this->belongsTo('App\TournamentLevel', 'level_id', 'id');
    }

    /**
     * Get All categories available
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */


    // We can use $tournament->categories()->attach(id);
    // Or         $tournament->categories()->sync([1, 2, 3]);
    public function categories()
    {
        return $this->belongsToMany('App\Category')
            ->withPivot('id')
            ->withTimestamps();
    }

    /**
     * Get All categoriesTournament that belongs to a tournament
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categoryTournaments()
    {
        return $this->hasMany(CategoryTournament::class);
    }


    /**
     * Get All categoriesSettings that belongs to a tournament
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function categorySettings()
    {
        return $this->hasManyThrough('App\CategorySettings', 'App\CategoryTournament');
    }

    /**
     * Get All competitors that belongs to a tournament
     * @param null $CategoryTournamentId
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function competitors($CategoryTournamentId = null)
    {
        return $this->hasManyThrough('App\CategoryTournamentUser', 'App\CategoryTournament');
    }


    /**
     * Get all Invitations that belongs to a tournament
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invites()
    {
        return $this->morphMany(Invite::class, 'object');
    }

    /**
     * @return mixed
     */
    public function settings()
    {
        $arrTc = CategoryTournament::select('id')->where('tournament_id', $this->id)->get();
        $settings = CategorySettings::whereIn('category_tournament_id', $arrTc)->get();
        return $settings;
    }

    public function settings2()
    {
        //TODO Should use hasManyThrough
        return $this->hasManyThrough('App\CategorySettings', 'App\CategoryTournament');
    }

    public function getCategoryList()
    {
        return $this->categories->lists('id')->all();
    }


    public function getRegisterDateLimitAttribute($date)
    {
        return $date;
    }

    public function setDateIniAttribute($date)
    {
        $this->attributes['dateIni'] = Carbon::createFromFormat('Y-m-d', $date);
    }

    public function setDateFinAttribute($date)
    {
        $this->attributes['dateFin'] = Carbon::createFromFormat('Y-m-d', $date);
    }

    public function setLimitRegisterDateAttribute($date)
    {
        $this->attributes['registerDateLimit'] = Carbon::createFromFormat('Y-m-d', $date);
    }

    public function isOpen()
    {
        return $this->type == 1;
    }

    public function needsInvitation()
    {
        return $this->type == 0;
    }
    public function getRouteKeyName()
    {
        return 'slug';
    }
    public function isDeleted(){
        return $this->deleted_at !=null;
    }

}