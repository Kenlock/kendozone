<?php

namespace App\Http\Controllers;

use App\FightersGroup;
use DaveJamesMiller\Breadcrumbs\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Xoco70\LaravelTournaments\Models\ChampionshipSettings;

class ChampionshipSettingsController extends Controller
{

    protected $defaultSettings;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param $championshipId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $championshipId)
    {

        try {
            if (Auth::check()) {
                App::setLocale(Auth::user()->locale);
            }
            $request->request->add(['championship_id' => $championshipId]);
            $setting = ChampionshipSettings::create($request->all());
            return Response::json(['setting' => $setting, 'msg' => trans('msg.category_create_successful'), 'status' => 'success']);
        } catch (Exception $e) {
            return Response::json(['msg' => trans('msg.category_create_error'), 'status' => 'error']);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param $championshipId
     * @param $championshipSettingsId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $championshipId, $championshipSettingsId)
    {
        try {
            //TODO As it is a WebService, Locale is resetted, as User info
            if (Auth::check()) {
                App::setLocale(Auth::user()->locale);
            }
            $setting = ChampionshipSettings::findOrFail($championshipSettingsId)->fill($request->all());

            // If we changed one of those data, remove tree
            if ($setting->isDirty('hasPreliminary') || $setting->isDirty('hasPreliminary') || $setting->isDirty('treeType')) {
                FightersGroup::where('championship_id', $championshipId)->delete();
            }
            $setting->save();
            return Response::json(['setting' => $setting, 'msg' => trans('msg.category_update_successful'), 'status' => 'success']);
        } catch (Exception $e) {
            return Response::json(['msg' => trans('msg.category_update_error'), 'status' => 'error']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ChampionshipSettings $cs
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ChampionshipSettings $cs)
    {
        try {
            $cs->delete();
            return Response::json(['msg' => trans('msg.category_delete_succesful'), 'status' => 'success']);
        } catch (Exception $e) {
            return Response::json(['msg' => trans('msg.category_delete_error'), 'status' => 'error']);
        }
    }

}
