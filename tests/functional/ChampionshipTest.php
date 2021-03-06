<?php
use App\Championship;
use App\Tournament;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Xoco70\LaravelTournaments\Models\ChampionshipSettings;

/**
 * User: juliatzin
 * Date: 10/11/2015
 * Time: 23:14
 */
class ChampionshipTest extends BrowserKitTest
{
    use DatabaseMigrations;

    protected $root;


    public function setUp()
    {
        parent::setUp();
        $this->root = factory(User::class)->create(['role_id' => Config::get('constants.ROLE_SUPERADMIN')]);
        $this->logWithUser($this->root);
    }

    /** @test */
    public function it_create_custom_championship()
    {
        Artisan::call('db:seed', ['--class' => 'CountriesSeeder', '--database' => 'sqlite']);
        Artisan::call('db:seed', ['--class' => 'TournamentLevelSeeder', '--database' => 'sqlite']);
        Artisan::call('db:seed', ['--class' => 'CategorySeeder', '--database' => 'sqlite']);
        Artisan::call('db:seed', ['--class' => 'GradeSeeder', '--database' => 'sqlite']);

        $category = factory(\App\Category::class)->make();

        $arrCat1 = $category->toArray();
        $this->actingAs($this->root, 'api')
            ->json('POST', '/api/v1/category/create', $arrCat1);
        unset($arrCat1['name']); // Remove category name that is translated and make test fail
        $this->seeInDatabase('category', $arrCat1);
    }

    /** @test */
    public function it_create_championship_settings()
    {
        Artisan::call('db:seed', ['--class' => 'TournamentLevelSeeder', '--database' => 'sqlite']);

        $tournament = factory(Tournament::class)->create(['user_id' => Auth::user()->id]);

        $champ0 = factory(Championship::class)->create(['tournament_id' => $tournament->id, 'category_id' => 1]);
        $cs0 = factory(ChampionshipSettings::class)->make(['championship_id' => $champ0->id]);
        $arrCs0 = $cs0->toArray();

        $this->actingAs($this->root, 'api')
            ->json('POST', '/api/v1/championships/' . $champ0->id . '/settings', $arrCs0)
            ->seeInDatabase('championship_settings', $arrCs0);
    }

    /** @test */
    public function it_edit_championship_settings()
    {

        Artisan::call('db:seed', ['--class' => 'TournamentLevelSeeder', '--database' => 'sqlite']);

        $tournament = factory(Tournament::class)->create(['user_id' => Auth::user()->id]);

        $champ0 = factory(Championship::class)->create(['tournament_id' => $tournament->id, 'category_id' => 1]);
        $cs0 = factory(ChampionshipSettings::class)->create(['championship_id' => $champ0->id]);
        $cs1 = factory(ChampionshipSettings::class)->make(['championship_id' => $champ0->id]);
        $arrCs1 = $cs1->toArray();


        $this->actingAs($this->root, 'api')
            ->json('PUT', '/api/v1/championships/' . $champ0->id . '/settings/' . $cs0->id, $arrCs1)
            ->seeInDatabase('championship_settings', $arrCs1);

    }


}
