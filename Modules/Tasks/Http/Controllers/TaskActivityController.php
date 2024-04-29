<?php

namespace Modules\Tasks\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Properties\Entities\PropertyActivity;

class TaskActivityController extends Controller
{
    public function taskAllActivities(Request $request, $id)
    {
        try {
            // $inspectionActivities = PropertyActivity::where('inspection_id', $id)->where('comment', '!=', null)->get();
            if ($request->data == 'all') {
                $taskActivities = PropertyActivity::where('task_id', $id)->with('task', 'messageMany')->get();
            } elseif ($request->data == 'comments') {
                $taskActivities = PropertyActivity::where('task_id', $id)->where('comment', '!=', null)->get();
            }

            // $inspectionCommentActivities = PropertyActivity::where('inspection_id', $id)->where('comment', '!=', null)->get();
            return response()->json([
                "data" => $taskActivities,
                // "taskCommentActivities" => $inspectionCommentActivities,
                "message" => "Successful"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => "error",
                "error" => ['error'],
                "message" => $th->getMessage(),
                "data" => []
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('tasks::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('tasks::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('tasks::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('tasks::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
