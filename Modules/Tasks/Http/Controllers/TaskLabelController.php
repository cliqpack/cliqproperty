<?php

namespace Modules\Tasks\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Tasks\Entities\TaskLabel;

class TaskLabelController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $tasklabel = TaskLabel::get();
            return response()->json(['taskLabel' => $tasklabel, 'status' => 'Successfully added taskLabel'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => ['error'], 'message' => $th->getMessage(), 'data' => []], 500);
        }
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
        try {
            $attributeNames = array(
                'task_id' => $request->task_id,
                'label'   => $request->label
            );
            $validator = Validator::make($attributeNames, [
                'task_id' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->getMessageBag()], 422);
            } else {
                $task = TaskLabel::where('task_id', $request->task_id)->first();
                if ($task) {
                    TaskLabel::where('task_id', $request->task_id)->delete();
                    foreach ($request->label as $label) {
                        $taskLabel = new TaskLabel();
                        $taskLabel->task_id = $request->task_id;
                        $taskLabel->label = $label;
                        $taskLabel->save();
                    }
                } else {
                    foreach ($request->label as $label) {
                        $taskLabel = new TaskLabel();
                        $taskLabel->task_id = $request->task_id;
                        $taskLabel->label = $label;
                        $taskLabel->save();
                    }
                    return response()->json(['task_id' => $request->task_id, 'message' => 'successfull'], 200);
                }
            }
        } catch (\Throwable $th) {
            return response()->json(["status" => false, "error" => ['error'], "message" => $th->getMessage(), "data" => []], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $tasklabel = TaskLabel::where('task_id', $id)->get();
            return response()->json(['taskLabel' => $tasklabel, 'status' => 'Successfully added taskLabel'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => ['error'], 'message' => $th->getMessage(), 'data' => []], 500);
        }
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
