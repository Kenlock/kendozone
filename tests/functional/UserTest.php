<?php
use App\Championship;
use App\ChampionshipSettings;
use App\Competitor;
use App\Tournament;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

/**
 * List of User Test
 *
 * it_denies_creating_an_empty_user()
 * mustBeAuthenticated()
 * it_create_user($delete = true)
 * it_edit_user()
 * it_denies_creating_user_without_password()
 * it_allow_editing_user_without_password()
 * you_must_be_the_user_to_edit_your_info_or_be_superuser()
 * check_you_can_see_user_info ( TODO )
 * User: juliatzin
 * Date: 10/11/2015
 * Time: 23:14
 */
class UserTest extends TestCase
{
//    use DatabaseTransactions;

    protected $user, $users, $root, $simpleUser; // $addUser,  $editUser,

    public function setUp()
    {
        parent::setUp();
        $this->simpleUser = factory(User::class)->create(['role_id' => Config::get('constants.ROLE_USER')]);
        $this->root = factory(User::class)->create(['role_id' => Config::get('constants.ROLE_SUPERADMIN')]);
    }

    /** @test */
    public function it_denies_creating_an_empty_user()
    {
        $this->logWithUser($this->root);

        $this->visit("/users/create")
            ->press(trans('core.save'))
            ->seePageIs('/users/create')
            ->see(trans('validation.filled', ['attribute' => "name"]))
            ->see(trans('validation.filled', ['attribute' => "email"]))
            ->see(trans('validation.filled', ['attribute' => "password"]));

    }

    /** @test */
    public function mustBeAuthenticated()
    {
        $this->visit('/users')
            ->seePageIs('/login');
    }


    /** @test */
    public function it_denies_creating_user_without_password()
    {
        $this->logWithUser($this->simpleUser);
        $this->visit('/')->dontSee(trans_choice('core.user', 2) . ' </a></li>');


        $this->logWithUser($this->root);

        $this->visit('/')
            ->click(trans_choice('core.user', 2))
            ->see(trans_choice('core.user', 2))
            ->click(trans('core.addModel', ['currentModelName' => trans_choice('core.user', 1)]))
            ->type('MyUser', 'name')
            ->type('julien@cappiello.fr3', 'email')
            ->type('julien', 'firstname')
            ->type('cappiello', 'lastname')
            ->press(Lang::get('core.save'))
            ->seePageIs('/users/create')
            ->see(Lang::get('validation.required', ['attribute' => "password"]))
            ->notSeeInDatabase('users', ['name' => 'MyUser']);

    }


    /** @test */
    public function it_delete_user()
    {
        $this->logWithUser($this->root);
        // Given

        $tournament = factory(Tournament::class)->create(['name' => 't1', 'user_id' => $this->simpleUser->id]);
        $ct1 = factory(Championship::class)->create(['tournament_id' => $tournament->id, 'category_id' => 1]);
        factory(ChampionshipSettings::class)->create(['championship_id' => $ct1->id]);
        factory(Competitor::class)->create(['championship_id' => $ct1->id]);

        $response = $this->call('DELETE', '/users/' . $this->simpleUser->slug);
        $this->assertEquals(200, $response->status());


        $this->seeIsSoftDeletedInDatabase('users', ['name' => $this->simpleUser->name])
            ->seeIsSoftDeletedInDatabase('tournament', ['user_id' => $this->simpleUser->id])
            ->seeIsSoftDeletedInDatabase('championship', ['id' => $ct1->id])
            ->seeIsSoftDeletedInDatabase('championship_settings', ['championship_id' => $ct1->id])
            ->seeIsSoftDeletedInDatabase('competitor', ['championship_id' => $ct1->id]);;

    }


    /** @test */
    public function you_must_be_the_user_to_edit_your_info_or_be_superuser()
    {
        $owner = factory(User::class)->create(['role_id' => Config::get('constants.ROLE_USER')]);

        // User 2 can edit his info
        $this->logWithUser($owner);
        $this->visit('/users/' . $owner->slug . '/edit')
            ->see(trans_choice('core.user', 1));

        // Now superuser can also edit User 2 Info
        $this->logWithUser($this->root);
        $this->visit('/users/' . $this->simpleUser->slug . '/edit')
            ->see(trans_choice('core.user', 1));

        //Now superuser cannot edit User 2 info
        $this->logWithUser($this->simpleUser);
        $this->visit('/users/' . $owner->slug . '/edit')
            ->see("403");

    }

    /** @test
     */
    public function check_you_can_see_user_info()
    {
        $user2 = factory(User::class)->create(['name' => 'AnotherUser']);

        $this->logWithUser($this->simpleUser);
        $this->visit('/users/' . $this->simpleUser->slug)
            ->dontSee("403")
            ->visit('/users/' . $user2->slug . '/edit')
            ->see("403");
    }

    //    /** @test */
//    public function it_create_user($delete = true)
//    {
//        $this->logWithUser($this->simpleUser);
//        $this->visit('/')->dontSee(trans_choice('core.user', 2).' </a></li>');
//
//        $this->logWithUser($this->root);
//
//        $test_file_path = base_path() . '/avatar2.png';
////        dd($test_file_path);
//        $this->assertTrue(file_exists($test_file_path), 'Test file does not exist');
//
//
//        $this->visit('/')
//            ->click(trans_choice('core.user', 2))
////            ->see(trans_choice('core.user', 2))
//            ->click(trans('core.addModel', ['currentModelName' => trans_choice('core.user', 1)]))
//            ->type('MyUser', 'name')
//            ->type('julien@cappiello.fr2', 'email')
//            ->type('julien', 'firstname')
//            ->type('cappiello', 'lastname')
//            ->type('111111', 'password')
//            ->type('111111', 'password_confirmation')
//            ->attach($test_file_path, 'avatar')
////            //File input avatar
//            ->press(trans('core.save'))
//            ->seePageIs('/users')
//            ->see(trans('msg.user_create_successful'))
//            ->seeInDatabase('users', ['name' => 'MyUser']);
//
//        $user = User::where('name', 'MyUser')->first();
//        File::delete(base_path() . '/'.$user->avatar);
//
//    }
//
//    /** @test */
//    public function it_edit_user()
//    {
//
//
//        $this->logWithUser($this->simpleUser);
//        $this->visit('/')->dontSee(trans_choice('core.user', 2).' </a></li>');
//
//        $this->logWithUser($this->root);
//
//
//        $this->visit('/users')
//            ->click($this->simpleUser->name)
//            ->type('juju', 'name')
//            ->type('juju@juju.com', 'email')
//            ->type('may', 'firstname')
//            ->type('1', 'lastname')
//            ->type('222222', 'password')
//            ->type('222222', 'password_confirmation')
//            ->type('44', 'avatar')
//            ->press(Lang::get('core.save'))
//            ->seePageIs('/users/')
//            ->seeInDatabase('users', ['name' => 'juju', 'email' => 'juju@juju.com']);
//
//    }

    /** @test */
    public function create_user()
    {
        $this->logWithUser($this->root);
        $user = factory(User::class)->make(['role_id' => Config::get('constants.ROLE_USER')]);
        $arrUser = json_decode(json_encode($user), true);

        $response = $this->json('POST', '/users', $arrUser);
//        dump($response);
        $this->seeInDatabase('users', $arrUser);

    }


    /** @test */
    public function edit_user()
    {
        $user = factory(User::class)->create(['role_id' => Config::get('constants.ROLE_USER')]);
    }



//    /** @test */
//    public function it_allow_editing_user_without_password()
//    {
//        $this->logWithUser($this->root);
//
//        $user = factory(User::class)->create(
//            [   'name' => 'MyUser',
//                'email' => 'MyUser@kendozone.com',
//                'role_id' => Config::get('constants.ROLE_USER'),
//                'password' => bcrypt('111111'),
//                'verified' => 1,]);
//
//        $oldPass = $user->password;
//        $this->visit('/users')
//            ->click("MyUser")
//            ->type('juju', 'name')
//            ->type('juju@juju.com', 'email')
//            ->type('may', 'firstname')
//            ->type('1', 'lastname')
////            ->select('3', 'role_id')
//            ->press(Lang::get('core.save'))
//            ->seePageIs('/users/')
//            ->seeInDatabase('users', ['name' => 'juju', 'email' => 'juju@juju.com']);
//
//        // Check that password remains unchanged
//        $user = User::where('email', '=', "juju@juju.com")->first();
//        $newPass = $user->password;
//        assert($oldPass == $newPass, true);
//
//    }


//    /** @test
//     */
//    public function you_can_change_your_password_and_login_with_new_data()
//    {
//        $this->simpleUser = factory(User::class)->create(['role_id' => Config::get('constants.ROLE_USER')]);
//        $this->logWithUser($this->simpleUser);
//        $this->visit("/users/".$this->simpleUser->slug."/edit/")
//            ->type('222222', 'password')
//            ->type('222222', 'password_confirmation')
//            ->press(trans('core.save'))
//            ->see(trans('msg.user_update_successful'));
//
//        //Logout
//        $this->click(trans('core.logout'))
//            ->seePageIs('auth/login');
//
//        // Login Again with new Data
//        $this->type($this->simpleUser->email, 'email')
//             ->type('222222', 'password')
//            ->press(trans('auth.signin'))
//            ->seePageIs('/');
//    }
}
